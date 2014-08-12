<?php
/**
 *	OUTRAGEdns specific stuff for content and models, etc.
 */


namespace OUTRAGEdns\Entity;

use \OUTRAGEweb\Entity;
use \OUTRAGEweb\Construct;


class Content extends Entity\Content
{
	/**
	 *	Let's define some actions.
	 */
	public function getter_actions()
	{
		$actions = new Construct\ObjectContainer();
		$endpoint = $this->settings->route ?: $this->settings->type."s";
		
		foreach($this->settings->actions as $action => $info)
		{
			if(!empty($info->id) && empty($this->id))
				continue;
			
			$path = "/".$endpoint."/".$action."/";
			
			if(!empty($info->id))
				$path .= $this->id."/";
			
			$actions[$action] = $path;
		}
		
		return $actions;
	}
}