<?php
namespace Hyphe\Functions;

function read_json($path, $toarray = false)
{
	if (file_exists($path))
	{
		$data = trim(file_get_contents($path));

		if (substr($data, 0,1) == "{" && strlen($data) > 3)
		{
			$json = json_decode($data);

			if ($toarray)
			{
				return (array) $json;
			}

			return $json;
		}
		else
		{
			if ($toarray)
			{
				return [];
			}

			return (object) [];
		}
	}
	else
	{
		\Moorexa\Event::emit('json.error', $path . 'doesn\'t exists. Please check file path.');
		
		if (env('bootstrap','debugMode') == 'on')
		{
			error($path . 'doesn\'t exists. Please check file path.');
		}
	}
}

function save_json($path, $data)
{
	if (is_array($data))
	{
		$dec = json_encode($data, JSON_PRETTY_PRINT);
	}
	else
	{
		$conv_arr = (array) $data;
		$dec = json_encode($conv_arr, JSON_PRETTY_PRINT);
	}

	if (file_exists($path) && is_writable($path))
	{
		file_put_contents($path, $dec);
	}
	else
	{
		\Moorexa\Event::emit('json.error', $path . 'isn\'t writable or doesn\'t exists.');

		if (env('bootstrap','debugMode') == 'on')
		{
			error($path . 'isn\'t writable or doesn\'t exists.');
		}
	}
}