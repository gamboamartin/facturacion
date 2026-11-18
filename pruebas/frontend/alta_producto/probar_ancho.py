"""Contrato estático de vista; no sustituye comprobación visual."""
from pathlib import Path
import hashlib, unittest
ROOT=Path(__file__).resolve().parents[3]
class AltaProductoAncho(unittest.TestCase):
 def test_fuente_historica(self):
  old=(Path(__file__).parent/'historico/alta-vendor.php').read_bytes()
  self.assertEqual(hashlib.sha256(old).hexdigest(),'25bfb057cf53d43e313c382b777226479f03618ff0a83ce9778eadebfe6b1b88')
 def test_solo_contenedor_adaptable(self):
  old=(Path(__file__).parent/'historico/alta-vendor.php').read_bytes()
  new=(ROOT/'views/com_producto/alta.php').read_bytes()
  self.assertEqual(old.count(b'<div class="container">'),1)
  self.assertEqual(new,old.replace(b'<div class="container">',b'<div class="container-fluid">',1))
 def test_contrato_alta_independiente(self):
  new=(ROOT/'views/com_producto/alta.php').read_text()
  self.assertEqual(new.count('<div class="container-fluid">'),1)
  self.assertIn('$controlador->link_com_producto_alta_bd',new)
  self.assertNotIn('$controlador->link_com_producto_modifica_bd',new)
if __name__=='__main__': unittest.main()
