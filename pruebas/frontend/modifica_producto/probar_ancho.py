from pathlib import Path
import hashlib,unittest
ROOT=Path(__file__).resolve().parents[3]
class ModificaProductoAncho(unittest.TestCase):
 def test_fuente_historica(self):
  old=(Path(__file__).parent/'historico/modifica-vendor.php').read_bytes()
  self.assertEqual(hashlib.sha256(old).hexdigest(),'cfc664b739ada9a41354fca4bbab3de43add7e55dcc4aa3d05d259f106f647d9')
 def test_cambio_minimo(self):
  old=(Path(__file__).parent/'historico/modifica-vendor.php').read_bytes()
  new=(ROOT/'views/com_producto/modifica.php').read_bytes()
  self.assertEqual(old.count(b'<div class="container">'),1)
  self.assertEqual(new,old.replace(b'<div class="container">',b'<div class="container-fluid">',1))
 def test_alta_conservada(self):
  self.assertEqual(hashlib.sha256((ROOT/'views/com_producto/alta.php').read_bytes()).hexdigest(),'de74e33aae0782cb9656eb1936c799c3d844e31743fe95c13a814146de194daf')
if __name__=='__main__':unittest.main()
