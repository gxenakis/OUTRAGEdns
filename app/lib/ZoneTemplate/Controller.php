<?php


namespace OUTRAGEdns\ZoneTemplate;

use \OUTRAGEdns\Entity;
use \OUTRAGEdns\Notification;
use \OUTRAGEdns\Response\EntityControllerResponse;


class Controller extends Entity\Controller
{
	/**
	 *	Called when we want to add a domain.
	 */
	public function add()
	{
		if($this->request->getMethod() == "POST" && $this->request->request->has("commit"))
		{
			if($this->form->validate($this->request->request))
			{
				$connection = $this->db->getAdapter()->getDriver()->getConnection();
				
				try
				{
					$connection->beginTransaction();
					
					$values = $this->form->getValues();
					
					if($this->user)
						$values["owner"] = $this->user;
					
					$this->content->save($values);
					
					$connection->commit();
					
					new Notification\Success("Successfully created the zone template: ".$this->content->name);
					
					return $this->app->redirect($this->content->actions->edit);
				}
				catch(Exception $exception)
				{
					$connection->rollback();
					
					new Notification\Error("This zone template wasn't added due to an internal error.");
				}
			}
		}
		
		# list all the nameservers that are currently defined
		$this->response->nameservers = [];
		
		if(!empty($this->config->records->soa->nameservers))
			$this->response->nameservers = array_merge($this->response->nameservers, $this->config->records->soa->nameservers->toArray());
		
		return EntityControllerResponse::createResponse($this);
	}
	
	
	/**
	 *	Called when we want to edit a domain.
	 */
	public function edit($id)
	{
		if(!$this->content->id)
			$this->content->load($id);
		
		if(!$this->allowed(__FUNCTION__))
		{
			new Notification\Error("You don't have access to this zone template.");
			
			return $this->app->redirect($this->content->actions->grid);
		}
		
		if($this->request->getMethod() == "POST" && $this->request->request->has("commit"))
		{
			if($this->form->validate($this->request->request))
			{
				$connection = $this->db->getAdapter()->getDriver()->getConnection();
				
				try
				{
					$connection->beginTransaction();
					
					$this->content->edit($this->form->getValues());
					
					$connection->commit();
					
					new Notification\Success("Successfully updated the zone template: ".$this->content->name);
				}
				catch(Exception $exception)
				{
					$connection->rollback();
					
					new Notification\Error("This zone template wasn't edited due to an internal error.");
				}
			}
		}
		
		# list all the nameservers that are currently defined
		$this->response->nameservers = [];
		
		if(!empty($this->config->records->soa->nameservers))
			$this->response->nameservers = array_merge($this->response->nameservers, $this->config->records->soa->nameservers->toArray());
		
		# oh, and it's a good idea to separate out the SOA record(s) from the other
		# records, that way we can make the SOA independently editable
		$this->response->records = [
			"soa" => [],
			"list" => [],
		];
		
		foreach($this->content->records as $record)
		{
			switch($record->type)
			{
				case "SOA":
					$this->response->records["soa"][] = $record;
				break;
				
				case "NS":
					$this->response->nameservers[] = $record->content;
				
				default:
					$this->response->records["list"][] = $record;
				break;
			}
		}
		
		$this->response->nameservers = array_unique($this->response->nameservers);
		
		return EntityControllerResponse::createResponse($this);
	}
	
	
	/**
	 *	Called when we want to remove a domain.
	 */
	public function remove($id)
	{
		if(!$this->content->id)
			$this->content->load($id);
		
		if(!$this->allowed(__FUNCTION__))
		{
			new Notification\Error("You don't have access to this zone template.");
			
			return $this->app->redirect($this->content->actions->grid);
		}
		
		$connection = $this->db->getAdapter()->getDriver()->getConnection();		
		
		try
		{
			$connection->beginTransaction();
			
			$this->content->remove();
			
			$connection->commit();
			
			new Notification\Success("Successfully removed the zone template: ".$this->content->name);
		}
		catch(Exception $exception)
		{
			$connection->rollback();
			
			new Notification\Error("This zone template wasn't removed due to an internal error.");
		}
		
		return $this->app->redirect($this->content->actions->grid);
	}
	
	
	/**
	 *	Called when we want show the grid view.
	 */
	public function grid()
	{
		if(!$this->response->templates)
		{
			$request = Content::find();
			$request->order("id ASC");
			
			if(!$this->app["session"]->get("godmode"))
				$request->where([ "owner" => $this->user->id ]);
			
			$this->response->templates = $request->get("objects");
		}
		
		return EntityControllerResponse::createResponse($this);
	}
}