<?php

/*
A very simple OAD database reader
The Open Anatomy Project
License: GNU General Public License
https://www.gnu.org/licenses/gpl-3.0.en.html
*/

// set classes for oad objects
class Structure_desc
{
  var $type;
  var $latin_name;
  var $english_name;
  var $location;
}

// set classes for database objects
class Structure_obj
{
  var $name;
  var $database;
  var $latin_name;
  var $position;
  var $file;
  var $file_size;
  var $describer_file;
  var $describer_file_size;
  var $texture_file;
  var $texture_file_size;
  var $location;
  var $polygon_count;
}

function compare_type($ind_a, $ind_b)
{
    return strcmp($ind_a, $ind_b);
}

function compare_name($ind_a, $ind_b)
{
    return strcmp($ind_a->latin_name, $ind_b->latin_name);
}

function print_files($file, $file_size)
{
	if($file != "")
	{
		$file_type = explode(".",$file);
		$output = '<br /><a href="'. $file . '">Download .' . $file_type[count($file_type)-1] . '</a>';
		if($file_size != "") { $output .= ' (' . $file_size . ')'; }
		return $output;				
	}
	else { return ""; }
}
// set vars like it's c++ not php
$structure_object_count = 0;
$structure_object[] = 0;
$oad_structure_count = 0;
$oad_structure[] = 0;
$oad_type_count = 0;
$oad_type[] = 0;

// set feed URL
$databases_url = 'http://open-anatomy.org/databases.xml';

// Read the feed URL
$databases = simplexml_load_file($databases_url) or die('Error: Cannot connect to the databases.xml file.');

// Output Databases
$output_databases = '<u>Connected Databases:</u><br /><ul>';
foreach($databases->children() as $database) 
{ 
    $output_databases .= '<li><b><a href="' . $database->WEBSITE . '">' . $database->NAME . '</a></b><br />';     
    $output_databases .= 'Database URL: <a href="' . $database->DATABASE_URL . '">' . $database->DATABASE_URL . '</a>; OAD-File: <a href="' . $database->OAD_FILE . '">' . $database->OAD_FILE . '</a>; License: ' . $database->LICENSE .  ';</li>'; 

    // Catch databases results
    if(simplexml_load_file($database->DATABASE_URL)) 
    {
		// load oad file
		if(simplexml_load_file($database->OAD_FILE))
		{
		// get all structures
    	    	$oad = simplexml_load_file($database->OAD_FILE);
			foreach ($oad->children() as $oad_object => $object) 
			{ 
				// Simple check if this structure already exists in our database
				$oad_exists = false;
				$oad_type_exists = false;
				foreach ($oad_structure as $structure)
				{
					if(isset($structure->latin_name))
					{ 
						// DIRRRRRRTY: json_encode the values to compare them
						if(json_encode($object->LATIN_NAME) == json_encode($structure->latin_name)) { $oad_exists = true; }
						if($oad_object == $structure->type) { $oad_type_exists = true; } 
					}
				}
				// only load types once into oad_type
				if($oad_type_exists == false)
				{
					$oad_type[$oad_type_count] = $oad_object;
					$oad_type_count = $oad_type_count + 1;
				}
				// OAD Structure doesn't exist yet? Put it in the database
				if($oad_exists == false)
				{
					$oad_structure[$oad_structure_count] = new Structure_desc;
					$oad_structure[$oad_structure_count] -> type = $oad_object;
					$oad_structure[$oad_structure_count] -> latin_name = $object->LATIN_NAME;
					$oad_structure[$oad_structure_count] -> english_name = $object->ENGLISH_NAME;
					$oad_structure[$oad_structure_count] -> location = $object->LOCATION;
					$oad_structure_count = $oad_structure_count + 1;
				}
			}
		}
		else
		{
			print '<b>ERROR</b> OAD File: ' . $database->OAD_FILE . ' required by ' . $database->WEBSITE . ' can not be reached<br />';
		}
		// Put structure in the database
    	$database_curr = simplexml_load_file($database->DATABASE_URL);
    	foreach ($database_curr->children() as $database_object => $object) 
    	{
			if($database_object == "STRUCTURE")
			{
				$structure_object[$structure_object_count] = new Structure_obj;
				$structure_object[$structure_object_count] -> name = $object->NAME;
				$structure_object[$structure_object_count] -> database = $database->NAME;
				$structure_object[$structure_object_count] -> latin_name = $object->LATIN_NAME;
				$structure_object[$structure_object_count] -> position = $object->POSITION;
				$structure_object[$structure_object_count] -> file = $object->FILE;
				$structure_object[$structure_object_count] -> file_size = $object->FILE_SIZE;
				$structure_object[$structure_object_count] -> describer_file = $object->DESCRIBER_FILE;
				$structure_object[$structure_object_count] -> describer_file_size = $object->DESCRIBER_FILE_SIZE;
				$structure_object[$structure_object_count] -> texture_file = $object->TEXTURE_FILE;
				$structure_object[$structure_object_count] -> texture_file_size = $object->TEXTURE_FILE_SIZE;
				$structure_object[$structure_object_count] -> polygon_count = $object->POLYGON_COUNT;
				$structure_object_count = $structure_object_count + 1;
			}
        }
    }
    else
    {
    	print '<b>ERROR</b> Database: ' . $database->DATABASE_URL . ' required by ' . $database->WEBSITE . ' can not be reached<br />';
    }
}

$output_databases .= '</ul>';

// Sort oad structues by name
usort($oad_type, "compare_type");
// Sort oad structues by name
usort($oad_structure, "compare_name");
foreach($oad_type as $type)
{
	print '<b><u>' . $type . ':</b></u><br>';
    // Now generate some output
    foreach ($oad_structure as $structure)
    {
		if($type == $structure->type)
		{
			print '<b>' . $structure->latin_name . ' (' . $structure->english_name . '):</b><ul>';
			$output_object = "";
			foreach($structure_object as $object)
			{
				// Get every object of the same name as the structure | DIRRRRRRTY: json_encode the values to compare them
				if(json_encode($object->latin_name) == json_encode($structure->latin_name))
				{
					$output_object .= '<li><u>' . $object->database . '</u>: ' . $object->name;
					if($object->polygon_count != '') { $output_object .= ' (Polygons: ' . $object->polygon_count . ')'; }
					$output_object .= print_files($object->file, $object->file_size);
					$output_object .= print_files($object->describer_file, $object->describer_file_size);
					$output_object .= print_files($object->texture_file, $object->texture_file_size);
					$output_object .= '</li>';
				}
			}
			print $output_object;
			print '</ul>';
		}
    }
}

print $output_databases;

?>
