<?php

namespace platz1de\FastAsyncWorldEdit\world\clientblock;

use pocketmine\scheduler\AsyncTask;

class RegistryUpdateTask extends AsyncTask
{
	public function onRun(): void
	{
		Registry::registerToNetwork();
	}
}