<?php

namespace App\Jobs;

use App\Http\Services\WhatsappBlastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $to;
    protected $templateId;
    protected $variables;

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
}