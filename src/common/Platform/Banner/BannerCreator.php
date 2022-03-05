<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Platform\Banner;

/**
 * @psalm-import-type BannerImportance from \Tuleap\Platform\Banner\Banner
 */
class BannerCreator
{
    private BannerDao $banner_dao;

    public function __construct(BannerDao $banner_dao)
    {
        $this->banner_dao = $banner_dao;
    }

    /**
     * @psalm-param BannerImportance $importance
     * @throws CannotCreateAnAlreadyExpiredBannerException
     */
    public function addBanner(string $message, string $importance, ?\DateTimeImmutable $expiration_date, \DateTimeImmutable $current_time): void
    {
        if ($expiration_date !== null && $current_time >= $expiration_date) {
            throw new CannotCreateAnAlreadyExpiredBannerException();
        }

        $this->banner_dao->addBanner($message, $importance, $expiration_date);
    }
}
