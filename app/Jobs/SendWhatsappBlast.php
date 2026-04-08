<?php

namespace App\Jobs;

use App\Http\Services\WhatsappBlastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $to;
    protected $templateId;
    protected $variables;

    public $tries = 3;

    public $timeout = 30;

    public function backoff()
    {
        return [10, 30, 60]; 
    }

    public function __construct($to, $templateId, $variables = [])
    {
        $this->to = $to;
        $this->templateId = $templateId;
        $this->variables = $variables;
    }

    public function handle(WhatsappBlastService $service)
    {
        $service->send(
            $this->to,
            $this->templateId,
            $this->variables
        );
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Whatsapp blast failed', [
            'to' => $this->to,
            'template_id' => $this->templateId,
            'variables' => $this->variables,
            'error' => $exception->getMessage(),
        ]);
    }
}