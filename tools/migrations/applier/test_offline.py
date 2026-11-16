from pathlib import Path
import unittest,subprocess,tempfile,json,shutil
P=Path(__file__).parent
class Offline(unittest.TestCase):
 def test_session_mode(self):
  r=subprocess.run(['php',str(P/'test_session_mode.php')],capture_output=True,text=True);self.assertEqual(r.returncode,0,r.stdout+r.stderr);self.assertIn('7 synthetic mode cases PASS',r.stdout)
 def test_policy_faults(self):
  with tempfile.TemporaryDirectory(prefix='migration-policy-') as d:
   r=subprocess.run(['php',str(P/'test_policy.php'),d],capture_output=True,text=True);self.assertEqual(r.returncode,0,r.stdout+r.stderr);self.assertGreater(json.loads(r.stdout)['checks'],50)
 def test_plan_offline(self):
  r=subprocess.run(['php',str(P/'migrate.php'),'plan'],capture_output=True,text=True);self.assertEqual(r.returncode,0,r.stdout+r.stderr);x=json.loads(r.stdout);self.assertTrue(x['apply_enabled']);self.assertEqual(len(x['tables']),4)
 def test_apply_requires_arguments_before_connection(self):
  r=subprocess.run(['php',str(P/'migrate.php'),'apply'],capture_output=True,text=True);self.assertEqual(r.returncode,1);self.assertEqual(json.loads(r.stdout)['error_code'],'required_argument')
 def test_package_drift(self):
  with tempfile.TemporaryDirectory() as d:
   p=Path(d)/'package';shutil.copytree(P/'package',p);(p/'cc_medidor.sql').write_text((p/'cc_medidor.sql').read_text()+'DROP TABLE x;')
   r=subprocess.run(['php',str(P/'migrate.php'),'plan','--package',str(p)],capture_output=True,text=True);self.assertEqual(r.returncode,1);self.assertEqual(json.loads(r.stdout)['error_code'],'package_pin')
 def test_default_plan_no_database(self):
  r=subprocess.run(['php',str(P/'migrate.php')],capture_output=True,text=True);self.assertEqual(r.returncode,0,r.stderr);self.assertEqual(json.loads(r.stdout)['mode'],'plan')
 def test_medidor_stub_only(self):
  r=subprocess.run(['php',str(P/'test_medidor.php')],capture_output=True,text=True);self.assertEqual(r.returncode,0,r.stdout+r.stderr);self.assertEqual(json.loads(r.stdout)['db_connections'],0)
 def test_index_order_canonical(self):
  code=r"""require 'Policy.php';$expected=json_decode(file_get_contents('package/cc_medidor.json'),true)['indexes'];$ix=[];foreach(array_reverse($expected) as $v)$ix[$v['name']]=$v;uksort($ix,'strcmp');if(canonical(array_values($ix))!==canonical($expected))exit(1);$ix['PRIMARY']['unique']=false;if(canonical(array_values($ix))===canonical($expected))exit(2);"""
  r=subprocess.run(['php','-r',code],cwd=P,capture_output=True,text=True);self.assertEqual(r.returncode,0,r.stdout+r.stderr)
 def test_lint(self):
  for p in P.glob('*.php'):
   r=subprocess.run(['php','-l',str(p)],capture_output=True,text=True);self.assertEqual(r.returncode,0,r.stdout+r.stderr)
if __name__=='__main__':unittest.main()
