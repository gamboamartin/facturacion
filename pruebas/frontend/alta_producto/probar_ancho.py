"""El rediseño solicitado el 25-11-2026 sustituye igualdad de bytes por contrato renderizado.
La referencia histórica sigue inmutable. El ancho real se comprueba en el laboratorio UI.
"""
from pathlib import Path
import hashlib
import sys
import unittest
from urllib.parse import parse_qs, urlsplit
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'productos_clientes'))
from contrato import renderizar

class AltaProductoAncho(unittest.TestCase):
    def test_fuente_historica(self):
        old = (Path(__file__).parent / 'historico/alta-vendor.php').read_bytes()
        self.assertEqual(hashlib.sha256(old).hexdigest(), '25bfb057cf53d43e313c382b777226479f03618ff0a83ce9778eadebfe6b1b88')

    def test_formulario_post_y_campos_originales(self):
        doc, _ = renderizar('alta')
        self.assertEqual(len(doc.forms), 1)
        form = doc.forms[0]
        self.assertEqual(form['method'].lower(), 'post')
        query = parse_qs(urlsplit(form['action']).query)
        self.assertEqual(query, {'seccion': ['com_producto'], 'accion': ['alta_bd'], 'session_id': ['QA-TOKEN']})
        self.assertEqual(form['id'], 'form_com_producto_alta')
        names = [field['name'] for field in doc.fields]
        for name in ['com_tipo_producto_id', 'codigo', 'descripcion', 'precio', 'cat_sat_producto', 'cat_sat_unidad_id', 'cat_sat_obj_imp_id']:
            self.assertEqual(names.count(name), 1, name)
        self.assertNotIn('codigo_sat', names)
        submit = next(button for button in doc.buttons if button.get('type') == 'submit')
        self.assertEqual((submit.get('name'), submit.get('value')), ('btn_action_next', 'modifica'))

    def test_busqueda_sat_y_estructura_adaptable(self):
        doc, html = renderizar('alta')
        self.assertEqual(doc.ids.count('datos_producto'), 1)
        self.assertEqual(doc.ids.count('producto-sat-estado'), 1)
        self.assertIn('com-cliente com-producto', html)
        self.assertIn('com-cliente-fields', html)
        self.assertIn('com-cliente-save-bar', html)
        self.assertNotIn('com-producto-sat-actual', html)
        self.assertEqual(len(doc.ids), len(set(doc.ids)), 'Identificadores duplicados')

if __name__ == '__main__':
    unittest.main()
