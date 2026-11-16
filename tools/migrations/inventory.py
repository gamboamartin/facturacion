#!/usr/bin/env python3
"""Offline migration artifact inventory. Never connects to or changes a database."""
import argparse
import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import sys

MAX_MANIFEST = 262144
MAX_SQL = 1048576
ID = re.compile(r'[a-z][a-z0-9_]{0,79}\Z')
HASH = re.compile(r'[0-9a-f]{64}\Z')

class InventoryError(ValueError):
    pass

def require(condition, code):
    if not condition:
        raise InventoryError(code)

def unique_object(pairs):
    result = {}
    for key, value in pairs:
        require(key not in result, 'duplicate_json_key')
        result[key] = value
    return result

def bounded_read(path, limit):
    require(path.is_file() and not path.is_symlink(), 'not_regular_file')
    with path.open('rb') as source:
        content = source.read(limit + 1)
    require(len(content) <= limit, 'file_too_large')
    return content

def inspect(manifest_path):
    """Validate local artifacts; an artifact hash is NOT evidence of DB application."""
    root = manifest_path.parent.resolve()
    try:
        document = json.loads(bounded_read(manifest_path, MAX_MANIFEST), object_pairs_hook=unique_object)
    except (json.JSONDecodeError, UnicodeDecodeError):
        raise InventoryError('invalid_json') from None
    require(type(document) is dict and set(document) == {'format_version', 'migrations', 'requirements'}, 'manifest_shape')
    require(type(document['format_version']) is int and document['format_version'] == 1, 'unsupported_version')
    migrations, requirements = document['migrations'], document['requirements']
    require(type(migrations) is list and len(migrations) <= 1000, 'migrations_shape')
    require(type(requirements) is list and len(requirements) <= 1000, 'requirements_shape')
    ids, paths, tables, output = set(), set(), set(), []
    for item in migrations:
        require(type(item) is dict and set(item) == {'id', 'path', 'sha256'}, 'migration_shape')
        name, relative, digest = item['id'], item['path'], item['sha256']
        require(type(name) is str and ID.fullmatch(name) is not None and name not in ids, 'migration_id')
        require(type(relative) is str and len(relative) <= 180, 'migration_path')
        part = PurePosixPath(relative)
        require(len(part.parts) == 2 and part.parts[0] == 'sql' and part.parts[1] == name + '.sql' and str(part) == relative, 'migration_path')
        require(relative not in paths, 'duplicate_path')
        require(type(digest) is str and HASH.fullmatch(digest) is not None, 'migration_hash')
        target = root / relative
        require(not (root / 'sql').is_symlink(), 'symlink_directory')
        require(target.resolve().is_relative_to(root), 'outside_root')
        actual = hashlib.sha256(bounded_read(target, MAX_SQL)).hexdigest()
        require(actual == digest, 'hash_mismatch')
        ids.add(name)
        paths.add(relative)
        output.append({'id': name, 'artifact_status': 'hash_verified', 'database_status': 'unknown'})
    for item in requirements:
        require(type(item) is dict and set(item) == {'table', 'status', 'sources'}, 'requirement_shape')
        table = item['table']
        require(type(table) is str and ID.fullmatch(table) is not None and table not in tables, 'requirement_table')
        require(item['status'] == 'pending_definition', 'requirement_status')
        require(type(item['sources']) is list and 0 < len(item['sources']) <= 20, 'requirement_sources')
        for source in item['sources']:
            require(type(source) is str and len(source) <= 200 and re.fullmatch(r'[A-Za-z0-9_./:-]+', source) is not None, 'requirement_source')
        tables.add(table)
    return {'format_version': 1, 'offline': True, 'execution_supported': False,
            'database_status': 'not_inspected', 'migration_artifacts': output,
            'pending_definitions': sorted(tables), 'deployment_readiness': 'not_assessed'}

def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=['validate', 'status'])
    parser.add_argument('--manifest', type=Path, default=Path(__file__).with_name('manifest.json'))
    args = parser.parse_args(argv)
    try:
        result = inspect(args.manifest)
    except (InventoryError, OSError) as error:
        # Do not echo file contents, SQL, paths or operating-system error details.
        code = str(error) if isinstance(error, InventoryError) else 'file_access_error'
        print(json.dumps({'valid': False, 'error': code}))
        return 1
    print(json.dumps({'valid': True, **result}, sort_keys=True))
    return 0

if __name__ == '__main__':
    sys.exit(main())
