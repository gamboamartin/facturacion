"""Prueba estática reproducible del candidato CSS; no sustituye navegador."""
from pathlib import Path
import re, unittest
ROOT=Path(__file__).resolve().parents[3]
HISTORICO=Path(__file__).parent/"historico"
class ContrasteAltaComplementoPago(unittest.TestCase):
 def test_ambito_y_propiedades(self):
  p=ROOT/'css/fc_complemento_pago/alta-contraste.css';old='';new=p.read_text()
  extra=re.sub(r'/\*.*?\*/','',new[len(old):],flags=re.S);blocks=re.findall(r'([^{}]+)\{([^{}]+)\}',extra);self.assertEqual(len(blocks),6)
  for selectors,body in blocks:
   for selector in selectors.split(','):self.assertTrue(selector.strip().startswith('#form_fc_complemento_pago_alta .bootstrap-select .dropdown-menu'))
   for bad in ['display','visibility','pointer-events','position']:self.assertNotIn(bad,body)
 def test_contraste_declarado(self):
  css=(ROOT/'css/fc_complemento_pago/alta-contraste.css').read_text()
  def lum(c):
   c=c.lstrip('#');c=''.join(x*2 for x in c) if len(c)==3 else c
   values=[int(c[i:i+2],16)/255 for i in [0,2,4]]
   return sum((v/12.92 if v<=.04045 else ((v+.055)/1.055)**2.4)*w for v,w in zip(values,[.2126,.7152,.0722]))
  for fg,bg in [('#302246','#fff'),('#302246','#f0e9ff'),('#fff','#7138e2'),('#6d6579','#fff')]:
   self.assertIn('color: '+fg+';',css);self.assertIn('background: '+bg+';',css)
   a,b=sorted([lum(fg),lum(bg)]);self.assertGreaterEqual((b+.05)/(a+.05),4.5)
 def test_vista_solo_version_css(self):
  p=ROOT/'views/fc_complemento_pago/alta.php';self.assertEqual(p.read_text().replace('<link rel="stylesheet" href="css/fc_complemento_pago/alta-contraste.css?v=1">\n', ""),(HISTORICO/p.relative_to(ROOT)).read_text())
if __name__=='__main__':unittest.main()
