<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesComplaint;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for updating an existing complaint card.
 *
 * The rules are identical to ComplaintStoreRequest because a
 * complaint is a full-replace resource: the edit form always submits
 * the complete card (bus, location, type, complaints, details), and
 * the service layer reconciles the incoming details against the
 * stored ones.
 *
 * If the two payloads ever diverge — e.g. a future "quick edit"
 * screen that only changes the status — this class can override
 * rules() without touching ComplaintStoreRequest.
 */
class ComplaintUpdateRequest extends FormRequest
{
    use ValidatesComplaint;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->complaintRules();
    }

    public function withValidator($validator): void
    {
        $this->validateComplaintDetails($validator);
    }
}
