<?php

namespace Glhd\FsOfHolding;

use BadMethodCallException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HoldingStream
{
	protected const PROTOCOL = 'fs-of-holding';
	
	protected string $api_key;
	
	protected string $path;
	
	protected int $position = 0;
	
	protected ?string $contents = null;
	
	public static function registerWrapper(): void
	{
		if (! in_array(self::PROTOCOL, stream_get_wrappers())) {
			stream_wrapper_register(self::PROTOCOL, self::class);
		}
	}
	
	public function __construct()
	{
		/** @noinspection LaravelFunctionsInspection */
		$this->api_key = config('openai.api_key')
			?? config('services.openai.api_key')
			?? config('services.openai.key')
			?? config('services.chatgpt.api_key')
			?? config('services.chatgpt.key')
			?? env('OPENAI_API_KEY')
			?? getenv('OPENAI_API_KEY');
	}
	
	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
	{
		if (! in_array($mode, ['r', 'r+', 'rb'])) {
			throw new BadMethodCallException('You may only reach INTO the filesystem of holding.');
		}
		
		$this->path = Str::after($path, static::PROTOCOL.'://');
		
		return true;
	}
	
	public function stream_stat(): array|bool
	{
		$this->contents ??= $this->reachInBag();
		
		return false;
	}
	
	public function stream_read(int $count): string|bool
	{
		$this->contents ??= $this->reachInBag();
		
		$read = substr($this->contents, $this->position, $count);
		$this->position += strlen($read);
		
		return $read;
	}
	
	public function stream_seek(int $offset, int $whence = SEEK_SET): bool
	{
		$length = strlen($this->stream);
		
		$position = match ($whence) {
			SEEK_SET => $offset,
			SEEK_CUR => $this->position + $offset,
			SEEK_END => $length + $offset,
			default => -1,
		};
		
		if ($position >= 0 && $position <= $length) {
			$this->position = $position;
			return true;
		}
		
		return false;
	}
	
	public function stream_close(): void
	{
		//
	}
	
	public function stream_tell(): int
	{
		return $this->position;
	}
	
	public function stream_eof(): bool
	{
		$this->contents ??= $this->reachInBag();
		
		return $this->position >= strlen($this->contents);
	}
	
	protected function reachInBag()
	{
		$response = Http::withToken($this->api_key)
			->asJson()
			->post('https://api.openai.com/v1/chat/completions', [
				'model' => 'gpt-4o-mini',
				'messages' => [
					[
						'role' => 'system',
						'content' => 'A user is about to provide you with the name of a file. Please respond with file contents that best matches the name that would likely surprise and delight the user.',
					],
					[
						'role' => 'system',
						'content' => 'The user is likely a Laravel programmer who is active in the Laravel community on Twitter. Cater the response to that audience, but don\'t rely too heavily on the "Laravel" part of their identity.',
					],
					[
						'role' => 'system',
						'content' => 'Please response *only* with the contents of the file.',
					],
					[
						'role' => 'user',
						'content' => $this->path,
					],
				],
			])
			->throw();
		
		$response = $response->json('choices.0.message.content');
		
		return preg_replace('/^```[a-z]+\s*|\s*```$/m', '', $response);
	}
}
