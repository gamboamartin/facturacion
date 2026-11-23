"""Render PHP real con datos sintéticos; no inicia aplicación, BD ni PAC."""
from html.parser import HTMLParser
from pathlib import Path
from datetime import datetime
from zoneinfo import ZoneInfo
import json
import os
import re
import shutil
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[3]
PARTIAL = ROOT / 'templates/inputs/fc_factura/informacion_global.php'


class Formulario(HTMLParser):
    def __init__(self):
        super().__init__()
        self.fields = {}
        self.options = {}
        self.labels = []
        self.tags = []
        self.current_select = None

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        self.tags.append((tag, attrs))
        if tag in {'input', 'select'}:
            self.fields[attrs['name']] = attrs
        if tag == 'select':
            self.current_select = attrs['name']
            self.options[self.current_select] = []
        if tag == 'option':
            self.options[self.current_select].append(attrs)
        if tag == 'label':
            self.labels.append(attrs['for'])

    def handle_endtag(self, tag):
        if tag == 'select':
            self.current_select = None

    def value(self, field):
        if field not in self.options:
            return self.fields[field]['value']
        options = self.options[field]
        return next((x['value'] for x in options if 'selected' in x), options[0]['value'])


class InformacionGlobalFormulario(unittest.TestCase):
    def render(self, row=None, values=None, post=None, context=None):
        script = '''$data=json_decode(stream_get_contents(STDIN),true);
$_POST=$data['post'];$controlador=new stdClass();
$controlador->valores=$data['values'];
$controlador->informacion_global_contexto=$data['context'];
if ($data['row']!==null) $controlador->row_upd=(object)$data['row'];
include $argv[1];'''
        result = subprocess.run(
            ['php', '-d', 'display_errors=stderr', '-d', 'error_reporting=32767', '-r', script, str(PARTIAL)],
            input=json.dumps({'row': row, 'values': values or {}, 'post': post or {}, 'context': context or {}}),
            capture_output=True, text=True, check=True,
        )
        self.assertEqual(result.stderr, '')
        parsed = Formulario()
        parsed.feed(result.stdout)
        return parsed, result.stdout

    def test_campos_vacios_sin_periodo_inventado(self):
        parsed, _ = self.render()
        names = {'global_periodicidad', 'global_meses', 'global_anio'}
        self.assertEqual(set(parsed.fields), names)
        self.assertEqual(set(parsed.labels), names)
        for name in names:
            self.assertEqual(parsed.value(name), '')
            self.assertNotIn('required', parsed.fields[name])
        current_year = datetime.now(ZoneInfo('America/Mexico_City')).year
        self.assertEqual(parsed.fields['global_anio']['min'], str(max(2019, current_year - 5)))
        self.assertEqual(parsed.fields['global_anio']['max'], str(current_year))

    def test_modifica_conserva_datos_del_registro(self):
        parsed, _ = self.render(row={'global_periodicidad': '04', 'global_meses': '11', 'global_anio': 2026})
        self.assertEqual([parsed.value(name) for name in parsed.fields], ['04', '11', '2026'])

    def test_alta_conserva_captura_anterior(self):
        parsed, _ = self.render(values={'global_periodicidad': '01', 'global_meses': '02', 'global_anio': '2025'})
        self.assertEqual([parsed.value(name) for name in parsed.fields], ['01', '02', '2025'])

    def test_post_tiene_preferencia_y_permite_vaciar(self):
        parsed, _ = self.render(
            row={'global_periodicidad': '04', 'global_meses': '11', 'global_anio': 2026},
            post={'global_periodicidad': '01', 'global_meses': '', 'global_anio': ''},
        )
        self.assertEqual([parsed.value(name) for name in parsed.fields], ['01', '', ''])

    def test_codigos_mes_y_bimestre_conservan_seleccion(self):
        for month in ['01', '09', '10', '12', '13', '18']:
            parsed, _ = self.render(row={'global_meses': month})
            self.assertEqual(parsed.value('global_meses'), month)
        self.assertEqual([x['value'] for x in parsed.options['global_periodicidad']], ['', '01', '02', '03', '04', '05'])
        self.assertEqual([x['value'] for x in parsed.options['global_meses']], [''] + [f'{x:02}' for x in range(1, 19)])

    def test_captura_no_puede_inyectar_html(self):
        attack = '\"><script>alert(1)</script><input autofocus onfocus="alert(2)'
        parsed, html = self.render(post={'global_anio': attack, 'global_periodicidad': attack, 'global_meses': attack})
        self.assertEqual(parsed.value('global_anio'), attack)
        self.assertNotIn('<script>', html)
        scripts = [attrs for tag, attrs in parsed.tags if tag == 'script']
        self.assertEqual(scripts, [
            {'type': 'application/json', 'id': 'fc-global-contexto'},
            {'src': 'js/fc_factura/informacion-global.js?v=1', 'defer': None},
        ])
        self.assertEqual(len(parsed.fields), 3)
        self.assertTrue(all('autofocus' not in attrs and 'onfocus' not in attrs for _, attrs in parsed.tags))

    def test_entradas_no_escalares_se_muestran_vacias(self):
        parsed, _ = self.render(post={'global_periodicidad': ['04'], 'global_meses': {'x': '11'}, 'global_anio': [2026]})
        self.assertEqual([parsed.value(name) for name in parsed.fields], ['', '', ''])

    def test_alta_y_modifica_comparten_parcial(self):
        alta = (ROOT / 'templates/inputs/fc_factura/alta.php').read_text()
        modifica = (ROOT / 'views/fc_factura/modifica.php').read_text()
        self.assertEqual(alta.count("include __DIR__ . '/informacion_global.php'"), 1)
        self.assertEqual(modifica.count("include __DIR__ . '/../../templates/inputs/fc_factura/informacion_global.php'"), 1)
        self.assertLess(alta.index("'/informacion_global.php'"), alta.index('fc-alta-actions'))
        self.assertLess(modifica.index("'/../../templates/inputs/fc_factura/informacion_global.php'"), modifica.index('botons/submit/modifica_bd.php'))

    def test_bloque_visible_solo_con_contexto_global_y_tipo_ingreso(self):
        row = {'com_sucursal_id': 10, 'cat_sat_tipo_de_comprobante_id': 1,
               'global_periodicidad': '04', 'global_meses': '11', 'global_anio': '2026'}
        context = {'sucursales': ['10'], 'tipos_ingreso': ['1']}
        parsed, _ = self.render(row=row, context=context)
        section = next(attrs for tag, attrs in parsed.tags if tag == 'section')
        self.assertNotIn('hidden', section)
        for attrs in parsed.fields.values():
            self.assertIn('required', attrs)
        self.assertEqual(parsed.value('global_anio'), '2026')
        for change in [{'com_sucursal_id': 20}, {'cat_sat_tipo_de_comprobante_id': 2}]:
            parsed, _ = self.render(row={**row, **change}, context=context)
            section = next(attrs for tag, attrs in parsed.tags if tag == 'section')
            self.assertIn('hidden', section)
            self.assertTrue(all('required' not in attrs for attrs in parsed.fields.values()))

    def test_contexto_html_solo_publica_identificadores_validos(self):
        attack = '</script><script>alert(1)</script>'
        _, html = self.render(context={'sucursales': ['10', attack, {'x': '3'}, '0'], 'tipos_ingreso': [1]})
        data = re.search(r'<script type="application/json" id="fc-global-contexto">(.*?)</script>', html).group(1)
        self.assertEqual(json.loads(data), {'sucursales': ['10'], 'tipos_ingreso': ['1']})
        self.assertNotIn(attack, html)

    def test_controlador_prepara_contexto_seguro_solo_en_alta_modifica(self):
        script = r'''
namespace gamboamartin\errores {
 class errores { public static bool $error=false; public function error(string $mensaje, array $data): array {self::$error=true;return compact('mensaje','data');} }
}
namespace gamboamartin\facturacion\controllers {
 class _base_system_fc {public \PDO $link;public string $accion='';public function init_inputs():array{return ['padre'=>true];}}
}
namespace {
 class Sentencia extends \PDOStatement {
  public function __construct(private array $rows){}
  public function execute(?array $params=null):bool{if($params!==['rfc'=>'XAXX010101000','tipo'=>'I'])throw new \RuntimeException('Parámetros inesperados');return true;}
  public function fetchAll(int $mode=\PDO::FETCH_DEFAULT,mixed ...$args):array{return $this->rows;}
 }
 class Conexion extends \PDO {
  public int $consultas=0;
  public function __construct(private array $rows){}
  public function prepare(string $query,array $options=[]):\PDOStatement|false{$this->consultas++;if(!str_contains($query,'UNION ALL'))throw new \RuntimeException('Consulta inesperada');return new Sentencia($this->rows);}
 }
 require $argv[1];
 $rows=[['contexto'=>'sucursal','id'=>10,'nombre'=>"  publico \t en general "],['contexto'=>'sucursal','id'=>20,'nombre'=>"PUBLICO EN GENERAL' onfocus='x"],['contexto'=>'tipo','id'=>1,'nombre'=>'']];
 $out=[];
 foreach(['alta','modifica','lista'] as $accion){
  $c=(new \ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_factura::class))->newInstanceWithoutConstructor();
  $c->accion=$accion;$c->link=new Conexion($rows);$base=$c->init_inputs();
  $out[$accion]=['contexto'=>$c->informacion_global_contexto,'consultas'=>$c->link->consultas,'base'=>$base];
 }
 echo json_encode($out);
}'''
        result = subprocess.run(['php', '-d', 'display_errors=stderr', '-r', script, str(ROOT/'controllers/controlador_fc_factura.php')],
                                capture_output=True, text=True, check=True)
        self.assertEqual(result.stderr, '')
        data = json.loads(result.stdout)
        for action in ['alta', 'modifica']:
            self.assertEqual(data[action], {'contexto': {'sucursales': ['10'], 'tipos_ingreso': ['1']},
                                            'consultas': 1, 'base': {'padre': True}})
        self.assertEqual(data['lista']['consultas'], 0)
        self.assertEqual(data['lista']['contexto'], {'sucursales': [], 'tipos_ingreso': []})

    def test_comportamiento_javascript_real(self):
        bundled = Path('/usr/lib/chatgpt/resources/cua_node/bin/node')
        node = os.environ.get('NODE_BIN') or shutil.which('node') or (str(bundled) if bundled.is_file() else None)
        self.assertIsNotNone(node, 'Se requiere Node o NODE_BIN para verificar la captura condicional.')
        result = subprocess.run([node, str(Path(__file__).with_name('probar_comportamiento.js')),
                                 str(ROOT/'js/fc_factura/informacion-global.js')],
                                capture_output=True, text=True, check=True)
        self.assertEqual(result.stderr, '')
        self.assertEqual(json.loads(result.stdout)['estado'], 'PASS_GLOBAL_UI_JS')


if __name__ == '__main__':
    unittest.main()
