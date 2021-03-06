<?php


namespace OUTRAGEdns\ZoneTemplate;

use \OUTRAGEdns\Entity;
use \OUTRAGEdns\User;
use \OUTRAGEdns\Record;
use \OUTRAGEdns\ZoneTemplateRecord;


class Content extends Entity\Content
{
	/**
	 *	Returns the user that owns this ZoneTemplate object.
	 */
	protected function getter_user()
	{
		if(!$this->owner)
			return null;
		
		return User\Content::find()->where([ "id" => $this->owner ])->get("first");
	}
	
	
	/**
	 *	Retrieve a list of all records owned by this zone template.
	 */
	protected function getter_records()
	{
		if(!$this->id)
			return null;
		
		return ZoneTemplateRecord\Content::find()->where([ "zone_templ_id" => $this->id ])->order("id ASC")->get("objects");
	}
	
	
	/**
	 *	How many records does this zone template have?
	 */
	protected function getter_records_no()
	{
		if(!$this->id)
			return 0;
		
		return ZoneTemplateRecord\Content::find()->where([ "zone_templ_id" => $this->id ])->get("count");
	}
	
	
	/**
	 *	Called when saving a new zone template.
	 */
	public function save($post = array())
	{
		if(!empty($post["owner"]) && is_object($post["owner"]))
			$post["owner"] = $post["owner"]->id;
		
		if(!parent::save($post))
			return false;
		
		$changed = false;
		
		if(array_key_exists("records", $post))
		{
			$changed = true;
			
			foreach($post["records"] as $item)
			{
				if(empty($item["zone_templ_id"]))
					$item["zone_templ_id"] = $this->id;
				
				$record = new ZoneTemplateRecord\Content();
				$record->save($item);
			}
		}
		
		unset($this->records);
		unset($this->records_no);
		
		if($changed)
			$this->log("records", [ "records" => $this->records ]);
		
		return $this->id;
	}
	
	
	/**
	 *	Called when editing a zone template.
	 */
	public function edit($post = array())
	{
		if(!empty($post["owner"]) && is_object($post["owner"]))
			$post["owner"] = $post["owner"]->id;
		
		if(!parent::edit($post))
			return false;
		
		$changed = false;
		
		if(array_key_exists("records", $post))
		{
			$changed = true;
			
			foreach($this->records as $record)
				$record->remove();
			
			foreach($post["records"] as $item)
			{
				if(empty($item["zone_templ_id"]))
					$item["zone_templ_id"] = $this->id;
				
				$record = new ZoneTemplateRecord\Content();
				$record->save($item);
			}
		}
			
		unset($this->records);
		unset($this->records_no);
		
		if($changed)
			$this->log("records", [ "records" => $this->records ]);
		
		return $this->id;
	}
	
	
	/**
	 *	Export all the records, applying any substitutions to any
	 *	of the fields or values out there.
	 */
	public function export($context = [])
	{
		$exports = [];
		$fields = array_flip((new Record\Content())->db_fields);
		
		foreach($this->records as $record)
		{
			$export = $record->toArray();
			$export = array_intersect_key($export, $fields);
			
			unset($export["id"]);
			
			foreach($export as $key => $value)
			{
				foreach($context as $search => $replace)
					$value = str_replace("[".$search."]", $replace, $value);
				
				$export[$key] = $value;
			}
			
			$exports[] = $export;
		}
		
		return $exports;
	}
}
