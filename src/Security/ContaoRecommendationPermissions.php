<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle\Security;

final class ContaoRecommendationPermissions
{
    public const USER_CAN_EDIT_ARCHIVE = 'contao_user.recommendations';
    public const USER_CAN_CREATE_ARCHIVES = 'contao_user.recommendationp.create';
    public const USER_CAN_DELETE_ARCHIVES = 'contao_user.recommendationp.delete';
}
