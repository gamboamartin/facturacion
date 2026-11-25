"""Verifica versionado de CSS en render PHP aislado, sin configuración ni servicios."""
from pathlib import Path
import os
import re
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[3]


class CacheCss(unittest.TestCase):
    def setUp(self):
        self.temporal = tempfile.TemporaryDirectory(prefix="qa-cache-css-")
        self.addCleanup(self.temporal.cleanup)
        self.raiz = Path(self.temporal.name)
        (self.raiz / "templates").mkdir()
        (self.raiz / "css").mkdir()
        shutil.copyfile(ROOT / "templates/css.php", self.raiz / "templates/css.php")
        self.asset = self.raiz / "css/overrides.css"
        self.asset.write_text("body { color: black; }\n")
        self.ejecutor = self.raiz / "render.php"
        self.ejecutor.write_text("""<?php
namespace config {
    class generales { public string $url_base = 'https://fixture.test/app/'; }
    class views {
        public string $url_assets = 'https://fixture.test/assets/';
        public string $url = 'https://fixture.test/app/';
    }
}
namespace { error_reporting(E_ALL); require __DIR__ . '/templates/css.php'; }
""")

    def render(self):
        resultado = subprocess.run(
            ["php", "-d", "display_errors=1", str(self.ejecutor)],
            capture_output=True, text=True, check=True, timeout=10,
        )
        self.assertEqual(resultado.stderr, "")
        self.assertNotRegex(resultado.stdout, r"Warning:|Notice:|Fatal error:")
        coincidencias = re.findall(
            r'href="(https://fixture\.test/app/css/overrides\.css\?v=([0-9]+))"',
            resultado.stdout,
        )
        self.assertEqual(len(coincidencias), 1)
        return resultado.stdout, coincidencias[0][0], int(coincidencias[0][1])

    def test_url_estable_sin_cambios(self):
        os.utime(self.asset, (1700000000, 1700000000))
        primera = self.render()
        segunda = self.render()
        self.assertEqual(primera, segunda)
        self.assertEqual(primera[2], 1700000000)

    def test_actualizacion_de_asset_invalida_version(self):
        os.utime(self.asset, (1700000000, 1700000000))
        primera = self.render()
        self.asset.write_text("body { color: purple; }\n")
        os.utime(self.asset, (1700000010, 1700000010))
        segunda = self.render()
        self.assertNotEqual(primera[1], segunda[1])
        self.assertEqual(segunda[2], 1700000010)
        self.assertEqual(
            primera[0].replace(primera[1], "OVERRIDES_URL"),
            segunda[0].replace(segunda[1], "OVERRIDES_URL"),
        )

    def test_asset_ausente_no_interrumpe_render(self):
        self.asset.unlink()
        salida, url, version = self.render()
        self.assertEqual(version, 0)
        self.assertIn('css/datatables.min.css', salida)


if __name__ == "__main__":
    unittest.main()
