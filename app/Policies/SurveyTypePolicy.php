<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SurveyType;
use App\Models\User;

class SurveyTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.survey_type.view');
    }

    public function view(User $user, SurveyType $surveyType): bool
    {
        return $user->can('master.survey_type.view');
    }

    public function create(User $user): bool
    {
        return $user->can('master.survey_type.manage');
    }

    public function update(User $user, SurveyType $surveyType): bool
    {
        return $user->can('master.survey_type.manage');
    }
}
