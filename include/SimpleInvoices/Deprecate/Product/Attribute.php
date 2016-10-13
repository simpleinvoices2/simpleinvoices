<?php
namespace SimpleInvoices\Deprecate\Product;

/**
 * THIS CLASS IS BROKEN!
 * 
 * Static calls when methods are not static!
 */
class Attribute
{
    /**
     * @param unknown $id
     */
    public function get($id)
    {
        $sql = "SELECT pa.*, pat.name AS `type` 
                FROM ".TB_PREFIX."products_attributes pa 
	                LEFT JOIN ".TB_PREFIX."products_attribute_type pat 
                        ON (pa.type_id = pat.id)
				WHERE pa.id = :id";

		$sth =  dbQuery($sql,':id',$id);
        $attribute = $sth->fetch();

        return $attribute;
    }

    /**
     * @param unknown $id
     * @return mixed
     */
    public function getName($id)
    {
        $sql = "SELECT * FROM ".TB_PREFIX."products_attributes WHERE id = :id";
        $sth =  dbQuery($sql,':id',$id);
        $attribute = $sth->fetch();
        return $attribute['name'];
    }

    /**
     * @param unknown $id
     */
    public function getType($id)
    {
        $attribute = product_attributes::get($id);
        return $attribute['type'];
    }

    /**
     * @param unknown $attribute_id
     * @param unknown $value_id
     */
    public function getValue($attribute_id, $value_id)
    {
       
        $type = product_attributes::getType($attribute_id);

        if($type == 'list')
        {
            $sql = "SELECT value FROM ".TB_PREFIX."products_values WHERE id = :id";
            $sth =  dbQuery($sql,':id',$value_id);
            $attribute = $sth->fetch();

            return $attribute['value'];
        } else {
            return $value_id;
        }

    }

    /**
     * @param unknown $id
     */
	public function getVisible($id)
    {
        $sql = "SELECT visible FROM ".TB_PREFIX."products_attributes WHERE id = :id";
        $sth =  dbQuery($sql,':id',$id);
        $attribute = $sth->fetch();
        if($attribute['visible'] =='1')
        {
             return true;
        } else {
            return false;
        }
    }

	public function getAll()
    {
        $sql = "SELECT * FROM ".TB_PREFIX."products_attributes";
        $sth =  dbQuery($sql);
        $attributes = $sth->fetchAll();
        return $attributes;
    }
}
