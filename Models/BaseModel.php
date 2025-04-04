<?php

namespace Models;

use App\Container;

class BaseModel {
	protected Container $di;

	public function setDi(Container $di)
	{
		if (empty($this->di)) {
			$this->di = $di;
		}
	}
}