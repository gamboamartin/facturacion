"""Rediseño autorizado 25-11-2026: conserva contrato de edición, no bytes del diseño anterior.
El control SAT permanece de solo lectura por el controlador heredado; no cambia esa regla.
"""
from pathlib import Path
import hashlib
import sys
import unittest
from urllib.parse import parse_qs, urlsplit
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'productos_clientes'))
from contrato import renderizar

class ModificaProductoAncho(unittest.TestCase):
    def test_fuente_historica(self):
        old = (Path(__file__).parent / 'historico/modifica-vendor.php').read_bytes()
        self.assertEqual(hashlib.sha256(old).hexdigest(), 'cfc664b739ada9a41354fca4bbab3de43add7e55dcc4aa3d05d259f106f647d9')

    def test_formulario_modifica_conserva_ruta_y_campos(self):
        doc, _ = renderizar('modifica')
        self.assertEqual(len(doc.forms), 1)
        form = doc.forms[0]
        self.assertEqual(form['id'], 'form_com_producto_modifica')
        self.assertEqual(form['method'].lower(), 'post')
        self.assertEqual(parse_qs(urlsplit(form['action']).query), {'seccion': ['com_producto'], 'accion': ['modifica_bd'], 'registro_id': ['77'], 'session_id': ['QA-TOKEN']})
        names = [field['name'] for field in doc.fields]
        for name in ['com_tipo_producto_id', 'codigo', 'descripcion', 'precio', 'codigo_sat', 'cat_sat_unidad_id', 'cat_sat_obj_imp_id']:
            self.assertEqual(names.count(name), 1, name)
        self.assertNotIn('cat_sat_producto', names)
        submit = next(button for button in doc.buttons if button.get('type') == 'submit')
        self.assertEqual((submit.get('name'), submit.get('value')), ('btn_action_next', 'modifica'))

    def test_sat_solo_lectura_y_descripcion_literal(self):
        doc, html = renderizar('modifica')
        sat = next(field for field in doc.fields if field['name'] == 'codigo_sat')
        self.assertIn('disabled', sat)
        self.assertNotIn('datos_producto', doc.ids)
        self.assertNotIn('producto-sat-estado', doc.ids)
        self.assertIn('com-producto-sat-actual', html)
        self.assertIn('&lt;img src=x onerror=alert(1)&gt;', html)
        self.assertNotIn('img', doc.tags)
        self.assertEqual(len(doc.ids), len(set(doc.ids)))

if __name__ == '__main__':
    unittest.main()
