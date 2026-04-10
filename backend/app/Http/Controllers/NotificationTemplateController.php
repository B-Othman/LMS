<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        // Return system defaults merged with any tenant overrides
        $systemTemplates = NotificationTemplate::whereNull('tenant_id')->get()->keyBy('type');
        $tenantOverrides = NotificationTemplate::where('tenant_id', $tenantId)->get()->keyBy('type');

        $merged = $systemTemplates->map(function (NotificationTemplate $system) use ($tenantOverrides) {
            return $tenantOverrides->get($system->type, $system);
        });

        return $this->success(NotificationTemplateResource::collection($merged->values())->resolve());
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_template' => ['required', 'string', 'max:255'],
            'body_html_template' => ['required', 'string'],
            'body_text_template' => ['required', 'string'],
            'channel' => ['required', 'in:email,in_app,both'],
            'is_active' => ['required', 'boolean'],
        ]);

        $system = NotificationTemplate::findOrFail($id);

        $tenantId = $request->user()->tenant_id;

        // Create or update a tenant-level override
        $template = NotificationTemplate::updateOrCreate(
            ['tenant_id' => $tenantId, 'type' => $system->type],
            $data,
        );

        return $this->success(new NotificationTemplateResource($template), 'Template updated.');
    }

    public function reset(int $id, Request $request): JsonResponse
    {
        $system = NotificationTemplate::findOrFail($id);
        $tenantId = $request->user()->tenant_id;

        NotificationTemplate::where('tenant_id', $tenantId)
            ->where('type', $system->type)
            ->delete();

        return $this->success(new NotificationTemplateResource($system), 'Template reset to system default.');
    }
}
