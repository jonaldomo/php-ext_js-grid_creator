<?php
/*
 *  php-ext_js-grid-creator
 *  Create an EXT-JS grid for all tables within a specified database
 *  @author John Moses
 *  @email moses.john.r@gmail.com
 *  @date July 9, 2011
 */


/*
 *  DB config
 *  Database is "crawled" using the `SHOW TABLES` command which is supported by MYSQL
 *  to support other databases, `SHOW TABLES` will need to be translated.
 */
$host = "";
$schema = "";
$username = "";
$password = "";

/*
 *  EXT-JS configuration
 */

$extDirectory = "/ext-4.0.2a";
$extLibrary = $extDirectory . "/ext-all-debug.js";
$extCss = $extDirectory . "/resources/css/ext-all.css";

/*
 *  Crawl database and loop through tables to create a .html, .js, .php file for each table
 */
if (!$link = mysql_connect($host, $username, $password)) {
    die("Could not connect: " . mysql_error());
}
print "Connected successfully<br>";

if (!$databaseSelected = mysql_select_db($schema))
{
    die("Could not select database $databaseSelected: " . mysql_error());
}

$tableQuery = "SHOW FULL TABLES";
$tableResult = mysql_query($tableQuery);

$columns = array();
while ($table = mysql_fetch_assoc($tableResult))
{
    $tableName = $table["Tables_in_hr_recruit"];
    $extFile = "$tableName.js";
    $htmlFile = "$tableName.html";
    $phpFile = "$tableName.php";
    /*Array
    *(
    *    [Tables_in_hr_recruit] => address
    *    [Table_type] => BASE TABLE
    *)
    */

    $columnQuery = "SHOW COLUMNS FROM " . $table["Tables_in_hr_recruit"];
    $columnResult = mysql_query($columnQuery);
    $x = 0;
    while ($columnAssoc = mysql_fetch_assoc($columnResult))
    {
        /*
        *Array
        *(
        *    [Field] => address_id
        *    [Type] => int(11)
        *    [Null] => NO
        *    [Key] => PRI
        *    [Default] => 
        *    [Extra] => auto_increment
        *)
        */   
        $columns[$tableName][$x] = $columnAssoc;
        $x++;  
    }    

    /*
     *  HTML file contents
     */
    $htmlContents = "<html><head><title>".$table["Tables_in_hr_recruit"]."</title><link rel='stylesheet' type='text/css' href='" . $extCss . "' /><script type='text/javascript' src='" . $extLibrary . "'></script><script type='text/javascript' src='" . $extFile . "'></script></head><body><div id='grid'></div></body></html>";

    /*
     *  HTML file creation
     */
    $htmlFileHandler = fopen($htmlFile, "w");
    if (!fwrite($htmlFileHandler, $htmlContents))
    {
        print "<br>Failed to write to html file $htmlFile";
    }
    print "Created $htmlFile<br>";
    fclose($htmlFileHandler);

    /*
     *  EXT_JS file contents
     */
    $extContents = "Ext.onReady(function(){\n"
                  ."$tableName = {};\n"
                  ."$tableName.proxy = Ext.create('Ext.data.proxy.Ajax', {\n"
                  ."reader: {\n"
                  ." type: 'json',\n"
                  ." root: 'results',\n"
                  ." totalProperty: 'total',\n"
                  ." id: 'id'\n"
                  ."},\n"
                  ."type: 'ajax',\n"
                  ."url: '$phpFile'\n"
                  ."});\n"
                  ."$tableName.store = Ext.create('Ext.data.Store', {\n"
                  ."autoLoad: true,\n"
                  ."fields: [";
    
    for ($x = 0; $x < count($columns[$tableName]); $x++ )
    {
        if($x == 0)
        {
            $extContents .= "{name: '" . $columns[$tableName][$x]['Field'] . "', type: 'string'}";
        }
        $extContents .= ",{name: '" . $columns[$tableName][$x]['Field'] . "', type: 'string'}";
    }

    $extContents .= "],\n"
                   ."proxy: $tableName.proxy});\n"
                   ."$tableName.grid = Ext.create('Ext.grid.Panel', {\n"
                   ."title: '$tableName',\n"
                   ."store: $tableName.store,\n"
                   ."loadMask: true,\n"
                   ."columns:[\n";

    for ($x = 0; $x < count($columns[$tableName]); $x++ )
    {
        if($x == 0)
        {
            $extContents .= "{text: '" . $columns[$tableName][$x]['Field'] . "', dataIndex: '" . $columns[$tableName][$x]['Field'] . "', flex: 1}\n";
        }
        $extContents .= ",{text: '" . $columns[$tableName][$x]['Field'] . "', dataIndex: '" . $columns[$tableName][$x]['Field'] . "', flex: 1}\n";
    }

    $extContents .= "]});"
                   ."$tableName.window = Ext.create('Ext.window.Window', {"
                   ."autoShow: true,"
                   ."layout: 'fit',"
                   ."maximized: true,"
                   ."items: [$tableName.grid]});"
                   ."});";                

    /*
     *  EXT_JS file creation
     */
    $extFileHandler = fopen($extFile, "w");
    if (!fwrite($extFileHandler, $extContents))
    {
        print "<br>Failed to write to js file $extFile";
    }
    print "Created $extFile<br>";
    fclose($extFileHandler);


    /*
     *  PHP file contents
     */
    $phpContents = "<?php\n"
                  ."mysql_connect('$host', '$username', '$password');\n"
                  ."mysql_select_db('$schema');\n"
                  ."\$query = 'select * from $tableName';\n"
                  ."\$result = mysql_query(\$query);\n"
                  ."\$nbrows = mysql_num_rows(\$result);\n"
                  ."if(\$nbrows>0){\n"
                  ."while(\$rec = mysql_fetch_array(\$result)){\n"
                  ."\$arr[] = \$rec;}\n"
                  ."\$jsonresult = json_encode(\$arr);\n"
                  ."print '({\"total\":\"'.\$nbrows.'\",\"results\":'.\$jsonresult.'})';\n"
                  ."}else{\n"
                  ."print '({\"total\":\"0\", \"results\":\"\"})';}?>";


    /*
     *  PHP file creation
     */
    $phpFileHandler = fopen($phpFile, "w");
    if (!fwrite($phpFileHandler, $phpContents))
    {
        print "<br>Failed to write to js file $phpFile";
    }
    print "Created $phpFile<br>";
    fclose($phpFileHandler);
}

?>
