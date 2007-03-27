<?
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('classes/amputee_fixer.php');

force_secure();

$user_netID = check_authentication();

echo '<h2>Reason Publication Setup</h2>';
if (!isset ($_POST['verify']))
{
        echo '<p>This script adds the commenting settings entity table to the publication / blog type and removes the issued newsletters option ';
        echo 'from the list of publication types.</p>';
}

if (isset ($_POST['verify']) && ($_POST['verify'] == 'Run'))
{
	$the_type = id_of('publication_type');
	
	if ($the_type > 0)
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('field'));
		$es->add_relation('entity.name = "publication_type"');
		$result = $es->run_one();
		$field = current($result);
		reason_update_entity($field->id(), get_user_id($user_netID), array('db_type' => "enum('Blog','Newsletter')"), false);
		if (add_entity_table_to_type('commenting_settings', 'publication_type'))
		{
			echo '<p>added entity table commenting_settings to publication_type.</p>';
		}
		else
		{
			echo '<p>the entity table commenting_settings is already part of the publication_type.</p>';
		}
		$q = 'SHOW COLUMNS FROM blog';
        $result = db_query($q, 'could not get fields');
        while($table = mysql_fetch_assoc($result))
        {
        		if ($table['Field'] == 'publication_type')
        		{
        			$type = $table['Type'];
        		}
        }
        if ($type == "enum('Blog','Newsletter','Issued Newsletter')")
        {
        	$q = "ALTER TABLE `blog` CHANGE `publication_type` `publication_type` ENUM('Blog','Newsletter')";
        	db_query($q, 'Could not alter entity table blog');
        	echo '<p>the blog entity table has been updated to remove issued newsletters as an option</p>';
        }
        else
        {
        	echo '<p>the blog entity table does not have issued newsletters as an option.</p>';
        	die;
        }
	}
	else
	{
		echo '<p>could not find either publication_type in the database - this script cannot proceed.</p>';
		die;
	}
}

else
{
	echo_form();
}

function add_entity_table_to_type($et, $type)
{
	$pub_type_id = id_of($type);
	
	$es = new entity_selector( id_of('master_admin') );
	$es->add_type( id_of('content_table') );
	$es->add_right_relationship($pub_type_id, relationship_id_of('type_to_table') );
	$es->add_relation ('entity.name = "'.$et.'"');
	$entities = $es->run_one();
	if (empty($entities))
	{
		$es2 = new entity_selector();
		$es2->add_type(id_of('content_table'));
		$es2->add_relation('entity.name = "'.$et.'"');
		$es2->set_num(1);
		$tables = $es2->run_one();
		if(!empty($tables))
		{
			$table = current($tables);
			create_relationship($pub_type_id,$table->id(),relationship_id_of('type_to_table'));
			$fixer = new AmputeeFixer();
			$fixer->fix_amputees($pub_type_id);
			$fixer->generate_report();
			return true;
		}
	}
	return false;
}

function echo_form()
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<p><input type="submit" name="verify" value="Run" /></p>';
	echo '</form>';
}

?>
