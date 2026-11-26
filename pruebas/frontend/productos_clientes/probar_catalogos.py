"""Contratos de tipos de producto y encabezados: render real, sin configuración ni BD.
El laboratorio HTTP separado verifica CRUD, permisos, SAT y ancho real en 1366/390/320.
"""
import unittest
from urllib.parse import parse_qs, urlsplit
from contrato import renderizar

class TiposProductoContrato(unittest.TestCase):
    def test_alta_genera_codigo_sin_campo_editable(self):
        doc, html = renderizar('alta', 'com_tipo_producto')
        self.assertEqual(len(doc.forms), 1)
        form = doc.forms[0]
        self.assertEqual(form['id'], 'form_com_tipo_producto_alta')
        self.assertEqual(form['method'].lower(), 'post')
        self.assertEqual(parse_qs(urlsplit(form['action']).query), {'seccion': ['com_tipo_producto'], 'accion': ['alta_bd'], 'session_id': ['QA-TOKEN']})
        self.assertEqual([field['name'] for field in doc.fields], ['descripcion'])
        self.assertIn('required', doc.fields[0])
        self.assertIn('com-tipo-producto-form', html)

    def test_modificacion_conserva_codigo_de_solo_lectura(self):
        doc, _ = renderizar('modifica', 'com_tipo_producto')
        form = doc.forms[0]
        self.assertEqual(form['id'], 'form_com_tipo_producto_modifica')
        query = parse_qs(urlsplit(form['action']).query)
        self.assertEqual(query, {'seccion': ['com_tipo_producto'], 'accion': ['modifica_bd'], 'registro_id': ['88'], 'session_id': ['QA-TOKEN']})
        self.assertEqual([field['name'] for field in doc.fields], ['codigo', 'descripcion'])
        self.assertIn('disabled', doc.fields[0])
        self.assertIn('required', doc.fields[1])
        self.assertEqual(len(doc.ids), len(set(doc.ids)))

    def test_encabezados_muestran_navegacion_y_no_api_tecnica(self):
        for section in ['com_producto', 'com_tipo_producto']:
            for action in ['alta', 'modifica']:
                with self.subTest(section=section, action=action):
                    _, html = renderizar(action, section)
                    self.assertIn('accion=alta', html)
                    self.assertIn('accion=lista', html)
                    self.assertNotIn('accion=get_data', html)
                    self.assertIn('com-cliente-header', html)
                    self.assertIn('com-cliente-save-bar', html)

if __name__ == '__main__':
    unittest.main()
