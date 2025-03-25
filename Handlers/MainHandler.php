<?php

class MainHandler {

	public function index()
	{
		$viewBuilder = new ViewBuilder();
		$viewBuilder->pickComponent('main');
		$viewBuilder->setTitle('Home');
		$viewBuilder->render();
	}
}