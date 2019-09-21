<?php

/* Holds logic to modify prop types

Prop types are categories that bookie prop templates are mapped to. 
Each prop type can in turn be assigned to a specific category.
The purpose of prop categories is to be able to use these when generating pre fight report and eventually also for alerts */

require_once('lib/bfocore/utils/db/class.PDOTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');


class PropTypeDAO
{
    public static function getPropTypes($category_id = null)
    {        
        $query = 'SELECT pt.*
                    FROM prop_types pt';
        $params = [];

        if ($category_id != null && is_numeric($category_id))
        {
            $query .= ' INNER JOIN prop_type_category ptc ON pt.id = ptc.proptype_id
                        INNER JOIN prop_categories pc ON ptc.category_id = pc.id
                        WHERE pc.id = ?';
            $params[] = $category_id;
        }

        $result = PDOTools::findMany($query, $params);
        $ret_proptypes = [];
        foreach ($result as $pt)
        {
            $temp_pt = new PropType($pt['id'], $pt['prop_desc'], $pt['negprop_desc']);
            $temp_pt->setEventProp($pt['is_eventprop']);
            $ret_proptypes[] = $temp_pt;
        }
        return $ret_proptypes;
    }

    //TODO: Currently not used. Remove or implement function in admin pages?    
    public static function assignPropTypeToCategory($proptype_id, $category_id)
    {
        $query = "INSERT INTO prop_type_category(proptype_id, category_id) VALUES (?,?)";
		$params = [$proptype_id, $category_id];
		try 
		{
			$id = PDOTools::insert($query, $params);
		}
		catch(PDOException $e)
		{
		    if($e->getCode() == 23000){
				throw new Exception("Duplicate entry", 10);	
		    }
		}
		return $id;
    }


}
