"""Ejecuta el JS real de alta y modificación contra un DOM ficticio sin API ni BD."""
import os
from pathlib import Path
import shutil
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[3]

class ConfiguracionAgentesFrontend(unittest.TestCase):
    def test_formularios_sin_y_con_asesor(self):
        candidatos = [os.environ.get('NODE_BIN'), shutil.which('node'),
                      '/home/em3/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node',
                      '/usr/lib/chatgpt/resources/cua_node/bin/node']
        node = next((p for p in candidatos if p and Path(p).is_file()), None)
        self.assertIsNotNone(node, 'Se requiere Node o NODE_BIN')
        result = subprocess.run([node, str(Path(__file__).with_name('probar_comportamiento.js')), str(ROOT)],
                                capture_output=True, text=True, timeout=20)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('PASS_FORMULARIOS_AGENTES', result.stdout)

    def test_plantilla_local_ignorada_y_comunes_en_git(self):
        for ruta in ('plantillas/Plantilla_Factura_ivitec.pdf', 'plantillas/local/otro.pdf'):
            p = subprocess.run(['git', '-C', str(ROOT), 'check-ignore', '--no-index', ruta], capture_output=True, text=True)
            self.assertEqual(p.returncode, 0, p.stderr)
        for ruta in ('plantillas/factura_base.pdf','plantillas/factura_aguaban.pdf','plantillas/complemento_aguaban.pdf'):
            p = subprocess.run(['git', '-C', str(ROOT), 'check-ignore', '--no-index', ruta], capture_output=True, text=True)
            self.assertEqual(p.returncode, 1, p.stderr)

if __name__ == '__main__':
    unittest.main()
