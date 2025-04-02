<?php

namespace Handlers;

use Views\ViewBuilder;

class MainHandler extends AbstractHandler {

	public function index()
	{
		$viewBuilder = new ViewBuilder();
		$viewBuilder->pickComponent('main');
		$viewBuilder->setTitle('Home');
		$viewBuilder->render();
	}
}