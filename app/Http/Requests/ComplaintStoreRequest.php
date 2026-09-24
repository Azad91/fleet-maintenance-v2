<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesComplaint;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for creating a new complaint card.
 *
 * All rules live in the ValidatesComplaint trait so this class and
 * ComplaintUpdateRequest cannot drift apart over time.
 *
 * The actual authorization check (can this user create complaints in
 * the current garage?) is enforced by ComplaintController via
 * Gate::authorize('create', Complaint::class), which delegates to
 * ComplaintPolicy. This FormRequest only validates the payload shape.
 */
class ComplaintStoreRequest extends FormRequest
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
