<?php
/*
* EasyCollection (0.9) - таблицы в админке Evolution CMS с использованием Easy UI
*/

class easyCollection
{

    public $doc_fields = array('type', 'contentType', 'pagetitle', 'longtitle', 'description', 'alias', 'link_attributes', 'published', 'pub_date', 'unpub_date', 'parent', 'isfolder', 'introtext', 'content', 'richtext', 'template', 'menuindex', 'searchable', 'cacheable', 'createdon', 'createdby', 'editedon', 'editedby', 'deleted', 'deletedon', 'deletedby', 'publishedon', 'publishedby', 'menutitle', 'donthit', 'privateweb', 'privatemgr', 'content_dispo', 'hidemenu', 'alias_visible');
    public $currentConfig;
    public $currentConfigKey;


    /**
     * easyCollection constructor.
     * @param $modx
     * @param null $params
     */
    public function __construct($modx, $params = null)
    {

        $this->modx = evolutionCMS();
        include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
        $tpl = DLTemplate::getInstance($modx);
        $this->tpl = $tpl;
        include_once(MODX_BASE_PATH . "assets/lib/MODxAPI/modResource.php");
        $doc = new modResource($modx);
        $this->doc = $doc;

        if (file_exists(MODX_BASE_PATH . "assets/plugins/easyCollection/config.inc.php")) include(MODX_BASE_PATH . "assets/plugins/easyCollection/config.inc.php");
        else {
            $config = array();
            $text = "<?php" . PHP_EOL . '$config=' . var_export($config, 1) . ';';

            $f = fopen(MODX_BASE_PATH . "assets/plugins/easyCollection/config.inc.php", 'w');
            fwrite($f, $text);
            fclose($f);
        }

        $this->config = $config;
        if (isset($_GET['key'])) {
            $this->currentConfig = $config[$_GET['key']];
            $this->idd = $_GET['id'];
        }

    }

    /**
     * @param $key
     * @param $id
     */
    function setCurrentConfig($key, $id)
    {
        $this->currentConfig = $this->config[$key];
        $this->idd = $id;
        $this->currentConfigKey = $key;
    }


    /**
     * Проверяем наличие значения в элеметне массива
     * @param $val
     * @param $elem
     * @return bool
     */
    function checkAvailabilityInElem($val, $elem)
    {
        $arr = array();
        foreach (explode(',', $elem) as $e) $arr[] = trim($e);
        if (in_array($val, $arr)) return true;
    }


    /**
     * Поиск индекса конфига
     * @param $doc
     * @return int|string
     */
    function checkAvailabilityInCofig($doc)
    {
        foreach ($this->config as $key => $c) {
            if ($this->checkAvailabilityInElem($doc['id'], $c['id']) || $this->checkAvailabilityInElem($doc['template'], $c['template'])) return $key;
        }
    }

    /**
     * @return array
     */
    function findHideChildren()
    {
        $ids = array();
        foreach ($this->config as $conf) {
            if ($conf['hideChildren']) {
                if ($conf['id']) foreach (explode(',', $conf['id']) as $id) $ids[] = $id;
                if ($conf['template']) {
                    $res = $this->modx->db->query('Select `id` from ' . $this->modx->getFullTableName('site_content') . ' where `template` in (' . $conf['template'] . ')');
                    while ($row = $this->modx->db->getRow($res)) $ids[] = $row['id'];
                }
            }
        }
        return $ids;
    }

    /**
     *
     */
    function getTable()
    {
        $tplName = (isset($this->currentConfig['tplName'])) ? $this->currentConfig['tplName'] . '.tpl' : 'deafult.tpl';
        if (file_exists(MODX_BASE_PATH . 'assets/plugins/easyCollection/tpls/' . $tplName)) {
            $table = file_get_contents(MODX_BASE_PATH . 'assets/plugins/easyCollection/tpls/' . $tplName);
        } else $table = file_get_contents(MODX_BASE_PATH . 'assets/plugins/easyCollection/tpls/default.tpl');
        echo $this->tpl->parseChunk('@CODE: ' . $table, $this->getPlaceholders());
    }

    /**
     * @return array|void
     */
    function getPlaceholders()
    {
        if ($this->currentConfig['oneTable']) {
            $mode = 'table';
            $allowed_fields = $this->allowed_fields();
        } else $mode = 'docs';

        if ($this->currentConfig['name']) $pagetitle = $this->currentConfig['name'];
        else $pagetitle = $this->modx->db->getValue('Select pagetitle from ' . $this->modx->getFullTableName('site_content') . ' where id=' . $this->idd);

        $data = array(
            'mode' => $mode,
            'txt_search' => $_GET['txt_search'],
            'key' => $this->currentConfigKey,
            'id' => $this->idd,
            'pagetitle' => $pagetitle,
            'dc' => '',
            'table' => '',
            'form' => '',
            'button' => $this->tpl->parseChunk($this->currentConfig['button']),
            'js' => $this->tpl->parseChunk($this->currentConfig['js']));
        if (!$this->currentConfig) return;
        foreach ($this->currentConfig['fields'] as $field => $item) {
            if (!in_array($field, $this->doc_fields)) $tvList[] = $field;

            if (!$item['hidden']) $data['table'] .= '<th field="' . $field . '" style="width:' . $item['width'] . 'px; text-align:' . $item['align'] . ';" align="' . $item['align'] . '"';
            if ($item['sortable']) $data['table'] .= ' sortable="true"';
            switch ($item['type']) {
                case "image":
                case "file":
                    $data['table'] .= ' formatter="getImage"';
                    if ((is_array($allowed_fields)) && (in_array($field, $allowed_fields))) $data['form'] .= '<div style="margin-bottom:10px"><label>' . $item['name'] . '</label><input class="easyui-filebox" name="' . $field . '" style="width:100%" value=""></div>';
                    break;

                case "checkbox":
                    $data['table'] .= ' formatter="checkbox"';
                    break;

                case "text-break":
                    $data['table'] .= ' formatter="textBreak"';
                    if ((is_array($allowed_fields)) && (in_array($field, $allowed_fields))) $data['form'] .= '<div style="margin-bottom:10px"><label>' . $item['name'] . '</label><textarea class="easyui-textbox" data-options="multiline:true" name="' . $field . '" style="width:100%;height:150px;"></textarea></div>';
                    break;

                case "select":
                    $data['form'] .= '<div style="margin-bottom:10px"><label>' . $item['name'] . '</label><select name="' . $field . '" style="width:100%" class="easyui-combobox">';
                    if ($item['elemets']) $elems = $item['elemets'];
                    else if (!$this->currentConfig['oneTable']) {
                        $elements = $this->modx->db->getValue('SELECT `elements` FROM
							' . $this->modx->getFullTableName('site_tmplvars') . ' WHERE name="' . $field . '"');

                        if ($elements) {
                            $eval = substr($elements, 0, 1);

                            if ($eval == '@') {
                                /*$snippet = trim(substr($elements, 6));
                                $elems = eval($snippet);*/
                            } else $elems = $elements;
                        }
                    }

                    if ($elems) {
                        $elems = explode('||', $elems);
                        foreach ($elems as $el) {
                            $str = explode('==', $el);
                            if (!$str[1]) $str[1] = $str[0];
                            $data['form'] .= '<option value="' . htmlspecialchars($str[0]) . '">' . $str[1] . '</option>';
                        }
                    }
                    $data['form'] .= '</select></div>';
                    break;

                case "date":
                    $data['table'] .= ' formatter="timeConverter"';
                    if ((is_array($allowed_fields)) && (in_array($field, $allowed_fields))) $data['form'] .= '<div style="margin-bottom:10px"><label>' . $item['name'] . '</label><input class="easyui-datebox" name="' . $field . '" style="width:100%"></div>';
                    $data['dc'] .= 'if (row.' . $field . ') row.' . $field . ' =  timeConverter(row.' . $field . ');';
                    break;

                default:
                    if ((is_array($allowed_fields)) && (in_array($field, $allowed_fields))) $data['form'] .= '<div style="margin-bottom:10px"><label>' . $item['name'] . '</label><input class="easyui-textbox" name="' . $field . '" style="width:100%"></div>';
                    break;
            }
            if (!$item['hidden']) $data['table'] .= '>' . $item['name'] . '</th>';
        }
        return $data;
    }

    /**
     * @param $data
     */
    function getDataDocuments($data)
    {
        function setLinkPage($data)
        {
            $data['edit'] = '<a href="index.php?a=27&id=' . $data['id'] . '" target="main"><i class="fa fa-pencil-square-o" aria-hidden="true" id="edit' . $data['id'] . '"></i></a>';
            return $data;
        }

        $tvList = array();
        foreach ($this->currentConfig['fields'] as $field => $item) {
            if (!in_array($field, $this->doc_fields)) $tvList[] = $field;
        }


        $page = isset($data['page']) ? intval($data['page']) : 1;
        $rows = isset($data['rows']) ? intval($data['rows']) : 100;
        $sort = isset($data['sort']) ? $data['sort'] : 'id';
        $order = isset($data['order']) ? $data['order'] : 'asc';

        if (($this->currentConfig['orderBy']) && (!isset($data['sort']))) {
            $orderby = $this->currentConfig['orderBy'];
        } else $orderby = $sort . ' ' . $order;
        $offset = ($page - 1) * $rows;

        if (isset($_GET['txt_search'])) {
            $filters = str_replace('[+txt_search+]', $this->modx->db->escape($_GET['txt_search']), $this->currentConfig['search']);
        }


        $key = (int)$_GET['key'];
        if ($this->currentConfig['prepare']) $prepare = ',' . $this->currentConfig['prepare'];

        $fields = array(
            'api' => 1,
            'parents' => $this->idd,
            'tvPrefix' => '',
            'tvList' => implode(',', $tvList),
            'JSONformat' => 'new',
            'filters' => $filters,
            'showNoPublish' => 1,
            'display' => $rows,
            'offset' => $offset,
            'orderBy' => $orderby,
            'filters' => $filters,
            'prepare' => 'setLinkPage' . $prepare);


        echo $this->modx->runSnippet('DocLister', $fields);
        exit();
    }

    /**
     * @param $data
     */
    function getDataOneTable($data)
    {
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $rows = isset($data['rows']) ? intval($data['rows']) : 100;
        $sort = isset($data['sort']) ? $data['sort'] : '';
        $order = isset($data['order']) ? $data['order'] : '';


        if ((!isset($data['order'])) && ($this->currentConfig['orderBy'])) {
            $orderby = $this->currentConfig['orderBy'];
        } else $orderby = $sort . ' ' . $order;
        $offset = ($page - 1) * $rows;

        if (isset($_GET['txt_search'])) {
            $filters = str_replace('[+txt_search+]', $this->modx->db->escape($_GET['txt_search']), $this->currentConfig['search']);
        }

        $fields = array(
            'api' => 1,
            'controller' => 'onetable',
            'idType' => 'documents',
            'table' => $this->currentConfig['oneTable'],
            'ignoreEmpty' => 1,
            'JSONformat' => 'new',
            'display' => $rows,
            'offset' => $offset,
            'orderBy' => $orderby,
            'addWhereList' => $filters,
            'prepare' => $this->currentConfig['prepare']
        );


        if (!$orderby) unset($fields['orderBy']);
        echo $this->modx->runSnippet('DocLister', $fields);
        exit();
    }

    /**
     * @param $data
     * @return mixed
     */
    function editDataDocuments($data)
    {
        $this->uploadFiles();
        $this->doc->edit($data['id']);
        unset($data['id']);
        foreach ($_POST as $key => $val) $doc->set($key, $val);
        return $this->doc->save(false, true);
    }


    /**
     * Оставляем разрешенные поля для кастомной таблицы
     * @param $data
     * @return array
     */
    function last_allowed_fields($data)
    {
        $allowed_fields = $this->allowed_fields();
        $fields = array();
        foreach ($data as $key => $val) {
            if (in_array($key, $allowed_fields)) $fields[$key] = $this->modx->db->escape($val);
        }
        return $fields;
    }


    /**
     * Находим поля кастомной таблицы
     * @return array
     */
    function allowed_fields()
    {
        $table_name = $this->modx->db->config['table_prefix'] . $this->currentConfig['oneTable'];
        $fields_table = $this->modx->db->getValue('SELECT GROUP_CONCAT(COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = "' . $table_name . '"');

        //Добавляем специально разрешенные поля (не забываем их обработать в afterPrepare)
        if ($this->currentConfig['allowed_fields']) $fields_table .= ',' . $this->currentConfig['allowed_fields'];

        $allowed_fields = explode(',', $fields_table);
        return $allowed_fields;
    }

    /**
     * @param $data
     * @return mixed
     */
    function editDataOneTable($data)
    {
        $this->uploadFiles();
        $table = $this->modx->getFullTableName($this->currentConfig['oneTable']);
        if ($this->currentConfig['afterPrepare']) $data = $this->modx->runSnippet($this->currentConfig['afterPrepare'], array('data' => $data));
        $result = $this->modx->db->update($this->last_allowed_fields($data), $table, 'id=' . $data['id']);
        return $result;
    }

    /**
     *
     */
    protected function uploadFiles()
    {
        if ((is_array($_FILES)) and (count($_FILES))) {
            foreach ($_FILES as $name => $item) {
                if (!$item['name']) continue;
                if (strpos($item['type'], 'image/') !== FALSE) $folder = 'assets/images/';
                else $folder = 'assets/files/';
                $file = $modx->stripAlias($item['name']);
                if (copy($item['tmp_name'], MODX_BASE_PATH . $folder . $file)) {
                    $_POST[$name] = $folder . $file;
                }
            }
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    function setCheckboxDocuments($data)
    {
        $this->doc->edit($data['id']);
        $this->doc->set($data['field'], $data['value']);
        return $this->doc->save(false, true);
    }

    /**
     * @param $data
     * @return mixed
     */
    function setCheckboxOneTable($data)
    {
        $checkbox = array();
        $checkbox[$data['field']] = $data['value'];
        $checkbox['id'] = $data['id'];
        return $this->editDataOneTable($checkbox);
    }

    /**
     * @param $data
     * @return mixed
     */
    function deleteDocuments($data)
    {
        return $this->modx->db->query('Update ' . $this->modx->getFullTableName('site_content') . ' set deleted=1 where id=' . $_POST['id']);
    }

    /**
     * @param $data
     * @return mixed
     */
    function unDeleteDocuments($data)
    {
        return $this->modx->db->query('Update ' . $this->modx->getFullTableName('site_content') . ' set deleted=0 where id=' . $_POST['id']);
    }

    /**
     * @param $data
     * @return mixed
     */
    function deleteOneTable($data)
    {
        $table = $this->modx->getFullTableName($this->currentConfig['oneTable']);
        return $this->modx->db->query('Delete from ' . $table . ' where id=' . $data['id']);
    }

    /**
     * @param $data
     */
    function setIndexDocuments($data)
    {
        $mu = $data['index'] - 1;
        $this->modx->db->query('Update ' . $this->modx->getFullTableName('site_content') . ' set menuindex="' . $mu . '" where id in (' . implode(',', $data['ids']) . ')');

        $docs = array();
        $res = $this->modx->db->query('Select id,menuindex from ' . $this->modx->getFullTableName('site_content') . ' where parent=' . $data['parent'] . ' order by menuindex asc, id asc');
        while ($row = $this->modx->db->getRow($res)) {
            $docs[] = array('id' => $row['id'], 'menuindex' => $row['menuindex']);
        }

        foreach ($docs as $m => $doc) {
            $this->modx->db->query('Update ' . $this->modx->getFullTableName('site_content') . ' set `menuindex`="' . $m . '" where id=' . $doc['id']);
            $docs[$m]['menuindex'] = $m;
        }
    }
}