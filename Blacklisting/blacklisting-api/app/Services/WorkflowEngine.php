<?php
namespace App\Services;

use App\Models\BlacklistCase;
use App\Models\CaseEvent;
use App\Models\WorkflowRule;
use Exception;

class WorkflowEngine
{
    /**
     * Initialize a new case based on applicable rules.
     */
    public function initializeCase($tenantId, $data)
    {
        // Fetch the active version of the rule for this category
        $rule = WorkflowRule::where('tenant_id', $tenantId)
            ->where('category', $data['category'])
            ->orderBy('version', 'desc')
            ->first();

        if (!$rule) {
            throw new Exception("No active regulatory rule found for category: {$data['category']}");
        }

        $case = BlacklistCase::create([
            'tenant_id' => $tenantId,
            // Format: case-blc-001
            'case_number' => 'case-blc-' . str_pad(rand(1, 9999), 3, '0', STR_PAD_LEFT),
            'applicant_id' => $data['applicant_id'],
            'target_id' => $data['target_id'],
            'category' => $data['category'],
            'rule_id' => $rule->id,
            'current_stage' => 'initiated', // Base state
            'status' => 'active',
            'process_status' => 'running',
            'total_liability' => $data['total_liability'] ?? 0,
        ]);

        $this->recordEvent($case, 'case_created', ['initial_data' => $data], 'Application received.');

        return $case;
    }

    /**
     * Transition a case to a new stage dynamically using the JSON config rule.
     */
    public function transition(BlacklistCase $case, $targetStage, $userId, $payload = [])
    {
        $ruleConfig = $case->rule->config ?? [];
        $transitions = $ruleConfig['transitions'] ?? [];
        
        $validTransition = collect($transitions)->first(function ($t) use ($case, $targetStage) {
            return $t['from'] === $case->current_stage && $t['to'] === $targetStage;
        });

        if (!$validTransition) {
            throw new Exception("Invalid transition from '{$case->current_stage}' to '{$targetStage}'. Not permitted by rule config v{$case->rule->version}.");
        }

        $oldStage = $case->current_stage;
        $case->current_stage = $targetStage;
        $case->save();

        $this->recordEvent($case, 'stage_transition', [
            'from' => $oldStage,
            'to' => $targetStage,
            'user_id' => $userId,
            'details' => $payload
        ], 'Stage changed based on rule config.');

        return $case;
    }

    /**
     * Record an immutable event on the case timeline.
     */
    public function recordEvent(BlacklistCase $case, $eventType, $payload, $reason = null, $userId = null)
    {
        return CaseEvent::create([
            'tenant_id' => $case->tenant_id,
            'case_id' => $case->id,
            'event_type' => $eventType,
            'user_id' => $userId,
            'payload' => $payload,
            'reason' => $reason
        ]);
    }
}
