import contextlib
import copy
import hashlib
import io
import json
from pathlib import Path
import tempfile
import unittest
import inventory

class InventoryTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        self.path = self.root / 'manifest.json'
        self.base = {'format_version': 1, 'migrations': [], 'requirements': []}

    def write(self, document):
        self.path.write_text(json.dumps(document))

    def artifact(self):
        (self.root / 'sql').mkdir()
        content = b'-- inert test fixture; never executed\n'
        (self.root / 'sql/m001.sql').write_bytes(content)
        d = copy.deepcopy(self.base)
        d['migrations'] = [{'id': 'm001', 'path': 'sql/m001.sql', 'sha256': hashlib.sha256(content).hexdigest()}]
        return d

    def rejected(self, d, code):
        self.write(d)
        with self.assertRaisesRegex(inventory.InventoryError, '^' + code + '$'):
            inventory.inspect(self.path)

    def test_empty_does_not_claim_ready(self):
        self.write(self.base)
        r = inventory.inspect(self.path)
        self.assertEqual(r['database_status'], 'not_inspected')
        self.assertEqual(r['deployment_readiness'], 'not_assessed')
        self.assertFalse(r['execution_supported'])

    def test_hash_verified_is_not_applied(self):
        self.write(self.artifact())
        self.assertEqual(inventory.inspect(self.path)['migration_artifacts'][0]['database_status'], 'unknown')

    def test_version(self):
        for version in [True, 0, 2, '1', None]:
            self.rejected({**self.base, 'format_version': version}, 'unsupported_version')

    def test_unknown_manifest_key(self):
        self.rejected({**self.base, 'apply': True}, 'manifest_shape')

    def test_drift(self):
        d = self.artifact()
        (self.root / 'sql/m001.sql').write_bytes(b'changed')
        self.rejected(d, 'hash_mismatch')

    def test_missing_file(self):
        d = self.artifact()
        (self.root / 'sql/m001.sql').unlink()
        self.rejected(d, 'not_regular_file')

    def test_duplicate_id(self):
        d = self.artifact()
        d['migrations'] *= 2
        self.rejected(d, 'migration_id')

    def test_paths(self):
        d = self.artifact()
        for path in ['../m001.sql', '/tmp/m001.sql', 'sql/../m001.sql', 'sql//m001.sql', 'sql/other.sql', 'sql/m001.php', 'sql\\m001.sql']:
            candidate = copy.deepcopy(d)
            candidate['migrations'][0]['path'] = path
            self.rejected(candidate, 'migration_path')

    def test_symlink_file(self):
        d = self.artifact()
        f = self.root / 'sql/m001.sql'
        f.rename(self.root / 'original')
        f.symlink_to(self.root / 'original')
        self.rejected(d, 'not_regular_file')

    def test_symlink_directory(self):
        d = self.artifact()
        (self.root / 'sql').rename(self.root / 'other')
        (self.root / 'sql').symlink_to(self.root / 'other', target_is_directory=True)
        self.rejected(d, 'symlink_directory')

    def test_duplicate_json_key(self):
        self.path.write_text('{"format_version":1,"format_version":1}')
        with self.assertRaisesRegex(inventory.InventoryError, 'duplicate_json_key'):
            inventory.inspect(self.path)

    def test_size_bound(self):
        d = self.artifact()
        (self.root / 'sql/m001.sql').write_bytes(b'x' * (inventory.MAX_SQL + 1))
        self.rejected(d, 'file_too_large')

    def test_requirements_cannot_claim_ready(self):
        d = copy.deepcopy(self.base)
        d['requirements'] = [{'table': 'example_table', 'status': 'applied', 'sources': ['orm/example.php:1']}]
        self.rejected(d, 'requirement_status')

    def test_no_sql_or_error_contents_in_output(self):
        d = self.artifact()
        (self.root / 'sql/m001.sql').write_bytes(b'PRIVATE_SENTINEL')
        self.write(d)
        out = io.StringIO()
        with contextlib.redirect_stdout(out):
            code = inventory.main(['status', '--manifest', str(self.path)])
        self.assertEqual(code, 1)
        self.assertNotIn('PRIVATE_SENTINEL', out.getvalue())
        self.assertNotIn(str(self.root), out.getvalue())

    def test_real_manifest_four_blocked(self):
        r = inventory.inspect(Path(__file__).with_name('manifest.json'))
        self.assertEqual(r['pending_definitions'], ['base_movil_push_token', 'base_movil_sesion', 'cc_medidor', 'cc_tipo_medidor'])
        self.assertEqual(r['migration_artifacts'], [{
            'id': 'org_logo_es_principal',
            'artifact_status': 'hash_verified',
            'database_status': 'unknown'
        }, {
            'id': 'fc_listas_esquema_20261119',
            'artifact_status': 'hash_verified',
            'database_status': 'unknown'
        }])
        self.assertFalse(r['execution_supported'])
        self.assertEqual(r['database_status'], 'not_inspected')

if __name__ == '__main__':
    unittest.main()
