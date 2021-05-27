<?php
require_once ('templates/class/ConnectionFactoryMySqlPDO.class.php');
require_once ('templates/class/QueryExecutor.class.php');
require_once ('templates/class/Template.class.php');
require_once ('templates/class/Config.class.php');

function generate() {
    ini_set('max_execution_time', 0);
    init();
    $sql = 'SHOW TABLES';
    $ret = QueryExecutor::execute($sql);
    generateDTOObjects($ret);
    echo '<hr>';
    generateDAOObjects($ret);
    echo '<hr>';
    generateDAOExtObjects($ret);
}

function init() {
    $config = Config::getInstance();
    @mkdir("generated");
    @mkdir("generated/com/");
    @mkdir("generated/com/".$config->get('project_name'));
    @mkdir("generated/com/".$config->get('project_name')."/".$config->get('modelsFolder'));
    @mkdir("generated/com/".$config->get('project_name')."/".$config->get('modelsFolder')."/dto");
    @mkdir("generated/com/".$config->get('project_name')."/".$config->get('modelsFolder')."/dao");
    @mkdir("generated/com/".$config->get('project_name')."/".$config->get('modelsFolder')."/dao/ext");
    copy('templates/class/DAO.class.php', "generated/com/".$config->get('project_name')."/".$config->get('modelsFolder').'/dao/DAO.java');
}

function doesTableContainPK($row) {
    $row = getFields($row[0]);
    for ($j = 0;$j < count($row);$j++) {
        if ($row[$j][3] == 'PRI') {
            return true;
        }
    }
    return false;
}

function doesTableContainAutoIncrement($row) {
    $row = getFields($row[0]);
    for ($j = 0;$j < count($row);$j++) {
        if ($row[$j][5] == 'auto_increment') {
            return true;
        }
    }
    return false;
}

/**
 * Metodo que genera los objetos DTO
 */
function generateDTOObjects($ret) {
    $config = Config::getInstance();
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];//obtiene el nombre de la tabla
        $singularClassName = getDTOName($tableName);
        $template = new Template('templates/DTO.tpl');
        $tab = getFields($tableName);//obtiene los campos de la tabla
        $variables = "";
        $constructor = "";
        $toString ="";
        $getters = "";
        $setters = "";
        //variables
        for ($j = 0;$j < count($tab);$j++) {
            $variables.= "\tprivate ";
            if (isColumnTypeNumber($tab[$j][1])){
                $variables.=  "Integer ";
            } else if (isColumnTypeDate($tab[$j][1])){
                $variables.=  "java.util.Date ";
            } else if (isColumnTypeText($tab[$j][1])){
                $variables.=  "String ";
            } else if (isColumnTypeDecimal($tab[$j][1])){
                $variables.=  "Double ";
            } else {
                echo "<p style=\"color: red;\">dato no conocido para mapear: <b>" . $tableName . "</b>: " . $tab[$j][0] . " " . $tab[$j][1] . "</p>";
            }
            $variables.= getVarNameWithS($tab[$j][0]) . ";\n";
        }
        //constructor
        $camposObligatorios = array();
        for ($j = 0;$j < count($tab);$j++) {
            if ($tab[$j][2] == 'NO' && $tab[$j][5] != 'auto_increment') {
                $camposObligatorios[] = $tab[$j];
            }
        }
        $constructor.= "\tpublic " . $singularClassName . "DTO(";
        for ($j = 0;$j < count($camposObligatorios);$j++) {
            if ($j != 0) {
                $constructor.= ", ";
            }
            if (isColumnTypeNumber($camposObligatorios[$j][1])){
                $constructor.=  "Integer ";
            } else if (isColumnTypeDate($camposObligatorios[$j][1])){
                $constructor.=  "java.util.Date ";
            } else if (isColumnTypeText($camposObligatorios[$j][1])){
                $constructor.=  "String ";
            } else if (isColumnTypeDecimal($camposObligatorios[$j][1])){
                $constructor.=  "Double ";
            }
            $constructor.= getVarNameWithS($camposObligatorios[$j][0]);
        }
        $constructor.= ") {\n";
        for ($j = 0;$j < count($camposObligatorios);$j++) {
            $constructor.= "\t\t" . "this." . getVarNameWithS($camposObligatorios[$j][0]) . " = " . getVarNameWithS($camposObligatorios[$j][0]) . ";\n";
        }
        $constructor.= "\t}\n";
        //toString
        $toString.= "\tpublic String toString() {\n\t\treturn \"<" . $singularClassName . "DTO>\"";
        for ($j = 0;$j < count($tab);$j++) {
            $toString.= " + \"<". getVarNameWithS($tab[$j][0]) .">\" + this." . getVarNameWithS($tab[$j][0]) . " + \"</". getVarNameWithS($tab[$j][0]) .">\"\n\t\t\t";
        }
        $toString.= " + \"<" . $singularClassName . "DTO>\";\t}\n";
        //getters
        for ($j = 0;$j < count($tab);$j++) {
            $getters.= "\tpublic ";
            if (isColumnTypeNumber($tab[$j][1])){
                $getters.=  "Integer ";
            } else if (isColumnTypeDate($tab[$j][1])){
                $getters.=  "java.util.Date ";
            } else if (isColumnTypeText($tab[$j][1])){
                $getters.=  "String ";
            } else if (isColumnTypeDecimal($tab[$j][1])){
                $getters.=  "Double ";
            }
            $getters.= "get" . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "() {\n\t\treturn " . getVarNameWithS($tab[$j][0]) . ";\n\t}\n\r";
        }
        //setters
        for ($j = 0;$j < count($tab);$j++) {
            $setters.= "\tpublic void set" . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "(";
            if (isColumnTypeNumber($tab[$j][1])){
                $setters.=  "Integer ";
            } else if (isColumnTypeDate($tab[$j][1])){
                $setters.=  "java.util.Date ";
            } else if (isColumnTypeText($tab[$j][1])){
                $setters.=  "String ";
            } else if (isColumnTypeDecimal($tab[$j][1])){
                $setters.=  "Double ";
            }
            $setters.= getVarNameWithS($tab[$j][0]) . ") {\n\t\t" . "this." . getVarNameWithS($tab[$j][0]) . " = " . getVarNameWithS($tab[$j][0]) . ";\n\t}\n\r";
        }
        $template->set('project_name', $config->get('project_name'));
        $template->set('models_folder', $config->get('modelsFolder'));
        $template->set('table_name', $tableName);
        $template->set('date', date("Y-m-d H:i"));
        $template->set('singular_class_name', $singularClassName);
        $template->set('variables', $variables);
        $template->set('constructor', $constructor);
        $template->set('to_string', $toString);
        $template->set('getters', $getters);
        $template->set('setters', $setters);
        $template->write('generated/com/'.$config->get('project_name').'/'.$config->get('modelsFolder').'/dto/' . $singularClassName . 'DTO.java');
    }
}

/**
 * Metodo que genera los DAO Extendidos para poner consultas personalizadas
 */
function generateDAOExtObjects($ret) {
    $config = Config::getInstance();
    //recorre las tablas
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $pluralClassName = getClassName($tableName);
        $tab = getFields($tableName);
        $pk = '';
        $queryByField = '';
        $deleteByField = '';
        //recorre las columnas
        for ($j = 0;$j < count($tab);$j++) {
            if ($tab[$j][3] == 'PRI') {
                $pk = $tab[$j][0];
            } else {
                $queryByField.= "	public function buscar" . $pluralClassName . "Por" . getClassName($tab[$j][0]) . "(\$" . $tab[$j][0] . "){
		\$sql = 'SELECT * FROM " . $tableName . " WHERE " . $tab[$j][0] . " = ?';
        \$var1=\$" . $tab[$j][0] . ";
        \$stmt = \$this->conn->prepare(\$sql);
        \$stmt->bindparam(1, \$var1);
        \$stmt->execute();
        return \$stmt->fetchAll();
	}\n\n";
                $deleteByField.= "	public function eliminar" . $pluralClassName . "Por" . getClassName($tab[$j][0]) . "(\$" . $tab[$j][0] . "){
		\$sql = 'DELETE FROM " . $tableName . " WHERE " . $tab[$j][0] . " = ?';
        \$var1=\$" . $tab[$j][0] . ";
		\$stmt = \$this->conn->prepare(\$sql);
        \$stmt->bindparam(1, \$var1);
        \$cantidadEliminada=\$stmt->execute();
        return \$cantidadEliminada;
	}\n\n";
            }
        }
        if ($pk == '') {
            continue;
        }
        $template = new Template('templates/DAOExt.tpl');
        
        $template->set('project_name', $config->get('project_name'));
        $template->set('models_folder', $config->get('modelsFolder'));
        
        $template->set('date', date("Y-m-d H:i"));
        $template->set('plural_class_name', $pluralClassName);
        $template->set('table_name', $tableName);
        $template->set('queryByFieldFunctions', $queryByField);
        $template->set('deleteByFieldFunctions', $deleteByField);
        $file = 'generated/com/'.$config->get('project_name').'/'.$config->get('modelsFolder').'/dao/ext/' . $pluralClassName . 'ExtDAO.java';
        if (!file_exists($file)) {
            $template->write('generated/com/'.$config->get('project_name').'/'.$config->get('modelsFolder').'/dao/ext/' . $pluralClassName . 'ExtDAO.java');
        }
    }
}

/**
 * Metodo que genera los objetos DAO
 */
function generateDAOObjects($ret) {
    $config = Config::getInstance();
    //recorre las tablas
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $pluralClassName = getClassName($tableName);
        $singularClassName = getDTOName($tableName);
        $dtoName = strtolower($singularClassName[0]) . substr($singularClassName, 1, strlen($singularClassName));
        $tab = getFields($tableName);
        $prepareParameters = "";
        $prepareParametersWithPks = "";
        $prepareWhere = "";
        $prepareWhereInsert = "";
        $prepareWhere2 = "";
        $prepareWhere3 = "";
        $parameterSetter = "";
        $parameterSetterWithPks = "";
        $whereSetter = "";
        $whereSetter2 = "";
        $whereSetterInsert = "";
        $autogeneratedId = "";
        $insertFields = "";
        $updateFields = "";
        $questionMarks = "";
        $pk = '';
        $pks = array();
        $pk_type = '';
        $param = 0;
        $param2 = 0;
        $param3 = 0;
        $crearVariables="";
        $crearObjeto="";   
        $asignarAtributos="";
        //constructor
        $camposObligatorios = array();
        $camposOpcionales = array();
        for ($j = 0;$j < count($tab);$j++) {
            $crearVariables .= "\n\t\t\t$" . getVarNameWithS($tab[$j][0]) . " = \$fila['" . $tab[$j][0] . "'];";
            if ($tab[$j][2] == 'NO' && $tab[$j][5] != 'auto_increment') {
                $camposObligatorios[] = $tab[$j];
            } else{
                $camposOpcionales[] = $tab[$j];
            }
        }
        $crearObjeto.= "$$dtoName = new $singularClassName(";
        for ($j = 0;$j < count($camposObligatorios);$j++) {
            if ($j != 0) {
                $crearObjeto.= ",";
            }
            $crearObjeto.= "$" . getVarNameWithS($camposObligatorios[$j][0]);
        }
        $crearObjeto.= ");";
        for ($j = 0;$j < count($camposOpcionales);$j++) {
            $asignarAtributos.= "\n\t\t\t$".strtolower($singularClassName[0]) . substr($singularClassName, 1, strlen($singularClassName))."->set" . strtoupper(getVarNameWithS($camposOpcionales[$j][0]) [0]) . substr(getVarNameWithS($camposOpcionales[$j][0]), 1, strlen(getVarNameWithS($camposOpcionales[$j][0]))) . "(\$" . getVarNameWithS($camposOpcionales[$j][0]) . ");";
        }
        //recorre las columnas
        for ($j = 0;$j < count($tab);$j++) {
            if (doesTableContainAutoIncrement($ret[$i])) {
                if ($tab[$j][5] == 'auto_increment') {
                    $autogeneratedId.= "\n\t\t\t" . '$idGenerado=$this->conn->lastInsertId();' . "\n\t\t\t" . '$' . getVarName($tableName) . '->set' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . '($idGenerado);';
                }
            }
            if ($tab[$j][3] == 'PRI') {
                $pk = $tab[$j][0];
                $c = count($pks);
                $pks[$c] = $tab[$j][0];
                $pk_type = $tab[$j][1];
                $updateFields.= $tab[$j][0] . " = " . $tab[$j][0] . ", ";
                if ($tab[$j][5] != 'auto_increment') {
                    $param2++;
                    $insertFields.= $tab[$j][0] . ", ";
                    $questionMarks.= "?, ";
                    $prepareParametersWithPks.= "\n\t\t\t\$var" . $param2 . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                    $parameterSetterWithPks.= "\n\t\t\t\$stmt->bindparam(" . $param2 . ', $var' . $param2 . ");";
                    $prepareWhereInsert.= "\n\t\t\t\$var" . $param2 . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                    $whereSetterInsert .= "\n\t\t\t\$stmt->bindparam(" . $param2 . ', $var' . $param2 . ");";
                }
            } else {
                $param++;
                $param2++;
                $insertFields.= $tab[$j][0] . ", ";
                $updateFields.= $tab[$j][0] . " = ?, ";
                $questionMarks.= "?, ";
                $prepareParameters.= "\n\t\t\$var" . $param . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                $prepareParametersWithPks.= "\n\t\t\t\$var" . $param2 . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                $parameterSetter.= "\n\t\t\$stmt->bindparam(" . $param . ', $var' . $param . ");";
                $parameterSetterWithPks.= "\n\t\t\t\$stmt->bindparam(" . $param2 . ', $var' . $param2 . ");";
                $prepareWhereInsert.= "\n\t\t\t\$var" . $param2 . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                $whereSetterInsert .= "\n\t\t\t\$stmt->bindparam(" . $param2 . ', $var' . $param2 . ");";
            }
        }
        if ($pk == '') {
            continue;
        }
        if (count($pks) == 1) {
            $template = new Template('templates/DAO.tpl');
        } else {
            $template = new Template('templates/DAO_with_complex_pk.tpl');
        }
        $insertFields = substr($insertFields, 0, strlen($insertFields) - 2);
        $updateFields = substr($updateFields, 0, strlen($updateFields) - 2);
        $questionMarks = substr($questionMarks, 0, strlen($questionMarks) - 2);
        $s = '';
        $pkWhere = '';
        for ($z = 0;$z < count($pks);$z++) {
            $param++;
            $prepareWhere.= "\n\t\t\$var" . $param . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($pks[$z]) [0]) . substr(getVarNameWithS($pks[$z]), 1, strlen(getVarNameWithS($pks[$z]))) . "();";
            $param3++;
            $prepareWhere2 .= "\n\t\t\$var" . $param3 . ' = $' . getVarNameWithS($pks[$z]) . ";";
            $prepareWhere3 .= "\n\t\t\$var" . $param3 . ' = $${var_name}->get' . getClassName($pks[$z]) . "();";
            $whereSetter .= "\n\t\t\$stmt->bindparam(" . $param . ', $var' . $param . ");";
            $whereSetter2 .= "\n\t\t\$stmt->bindparam(" . $param3 . ', $var' . $param3 . ");";
            if ($z > 0) {
                $s.= ', ';
                $pkWhere.= ' AND ';
            }
            $s.= '$' . getVarNameWithS($pks[$z]);
            $pkWhere.= $pks[$z] . ' = ? ';
        }
        if ($s[0] == ',') {
            $s = substr($s, 1);
        }
        $template->set('project_name', $config->get('project_name'));
        $template->set('models_folder', $config->get('modelsFolder'));
        
        $template->set('prepare_where3', $prepareWhere3);
        $template->set('table_name', $tableName);
        $template->set('date', date("Y-m-d H:i"));
        $template->set('plural_class_name', $pluralClassName);
        $template->set('var_name', getVarName($tableName));
        $template->set('update_fields', $updateFields);
        $template->set('pk', $pk);
        $template->set('pk2', getClassName($pk));
        $template->set('prepare_parameters', $prepareParameters);
        $template->set('prepare_where', $prepareWhere);
        $template->set('parameter_setter', $parameterSetter);
        $template->set('where_setter', $whereSetter);
        $template->set('where_setter2', $whereSetter2);
        $template->set('insert_fields', $insertFields);
        $template->set('question_marks', $questionMarks);
        $template->set('prepare_parameters_with_pks', $prepareParametersWithPks);
        $template->set('parameter_setter_with_pks', $parameterSetterWithPks);
        $template->set('autogenerated_id', $autogeneratedId);
        $template->set('crear_variables', $crearVariables);
        $template->set('crear_objeto', $crearObjeto);
        $template->set('asignar_atributos', $asignarAtributos);
        $template->set('dto_name', $dtoName);
        
        $template->set('pk_where', $pkWhere);
        $template->set('pks', $s);
        $template->set('prepare_where2', $prepareWhere2);
        $template->set('prepare_where_insert', $prepareWhereInsert);
        $template->set('where_setter_insert', $whereSetterInsert);
       
        $template->write('generated/com/'.$config->get('project_name').'/'.$config->get('modelsFolder').'/dao/' . $pluralClassName . 'DAO.java');
    }
}

function isColumnTypeNumber($columnType) {
    if (strtolower(substr($columnType, 0, 3)) == 'int' || strtolower(substr($columnType, 0, 7)) == 'tinyint'
        || strtolower(substr($columnType, 0, 8)) == 'smallint' || strtolower(substr($columnType, 0, 9)) == 'mediumint'
        || strtolower(substr($columnType, 0, 6)) == 'bigint') {
        return true;
    }
    return false;
}

function isColumnTypeText($columnType) {
    if (strtolower(substr($columnType, 0, 4)) == 'char' || strtolower(substr($columnType, 0, 7)) == 'varchar') {
        return true;
    }
    return false;
}

function isColumnTypeDate($columnType) {
    if (strtolower(substr($columnType, 0, 4)) == 'date' || strtolower(substr($columnType, 0, 9)) == 'timestamp') {
        return true;
    }
    return false;
}

function isColumnTypeDecimal($columnType) {
    if (strtolower(substr($columnType, 0, 7)) == 'decimal') {
        return true;
    }
    return false;
}

function getFields($table) {
    $sql = 'DESC ' . $table;
    return QueryExecutor::execute($sql);
}

function getForeignKey($table, $column) {
    $sql = 'SELECT table_name, column_name, referenced_table_name, referenced_column_name 
    FROM
    information_schema.key_column_usage
    WHERE
    referenced_table_name IS NOT NULL
    AND CONSTRAINT_SCHEMA = database()
    AND table_name = \'' . $table . '\'
    AND column_name = \'' . $column . '\'';
    return QueryExecutor::execute($sql);
}

function isForeignKey($table, $column) {
    $sql = 'SELECT table_name, column_name, referenced_table_name, referenced_column_name 
    FROM
    information_schema.key_column_usage
    WHERE
    referenced_table_name IS NOT NULL
    AND CONSTRAINT_SCHEMA = database()
    AND table_name = \'' . $table . '\'
    AND column_name = \'' . $column . '\'';
    if (count(QueryExecutor::execute($sql))>0){
        return true;   
    } else {
        return false;
    }
}

function getClassName($tableName) {
    $tableName = strtoupper($tableName[0]) . substr($tableName, 1);
    for ($i = 0;$i < strlen($tableName);$i++) {
        if ($tableName[$i] == '_') {
            $tableName = substr($tableName, 0, $i) . strtoupper($tableName[$i + 1]) . substr($tableName, $i + 2);
        }
    }
    return $tableName;
}

function getDTOName($tableName) {
    $name = getClassName($tableName);
    if ($name[strlen($name) - 1] == 's') {
        $name = substr($name, 0, strlen($name) - 1);
    }
    return $name;
}

function getVarName($tableName) {
    $tableName = strtolower($tableName[0]) . substr($tableName, 1);
    for ($i = 0;$i < strlen($tableName);$i++) {
        if ($tableName[$i] == '_') {
            $tableName = substr($tableName, 0, $i) . strtoupper($tableName[$i + 1]) . substr($tableName, $i + 2);
        }
    }
    if ($tableName[strlen($tableName) - 1] == 's') {
        $tableName = substr($tableName, 0, strlen($tableName) - 1);
    }
    return $tableName;
}

function getVarNameWithS($tableName) {
    $tableName = strtolower($tableName[0]) . substr($tableName, 1);
    for ($i = 0;$i < strlen($tableName);$i++) {
        if ($tableName[$i] == '_') {
            $tableName = substr($tableName, 0, $i) . strtoupper($tableName[$i + 1]) . substr($tableName, $i + 2);
        }
    }
    return $tableName;
}

function copiar($fuente, $destino) {
    if (is_dir($fuente)) {
        $dir = opendir($fuente);
        while ($archivo = readdir($dir)) {
            if ($archivo != "." && $archivo != "..") {
                if (is_dir($fuente . "/" . $archivo)) {
                    if (!is_dir($destino . "/" . $archivo)) {
                        mkdir($destino . "/" . $archivo);
                    }
                    copiar($fuente . "/" . $archivo, $destino . "/" . $archivo);
                } else {
                    copy($fuente . "/" . $archivo, $destino . "/" . $archivo);
                }
            }
        }
        closedir($dir);
    } else {
        copy($fuente, $destino);
    }
}

function multiexplode($delimiters,$string) {   
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

function startsWith( $haystack, $needle ) {
     $length = strlen( $needle );
     return substr( $haystack, 0, $length ) === $needle;
}

function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

generate();