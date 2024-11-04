<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Share20\Manager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Server;
use OCP\Share;
use OCP\Share\Exceptions\ShareNotFound;

/**
 * Deletes shares with invalid data
 */
class DeleteInvalidShares implements IRepairStep {
	public function getName() {
		return 'Delete invalid shares';
	}

	private function checkShares(IOutput $output) {
		/** @var Manager $shareManager */
		$shareManager = Server::get(Share\IManager::class);
		$deleted = 0;
		/** @var Share\IShare $share */
		foreach ($shareManager->getAllShares() as $share) {
			try {
				$shareManager->checkShare($share);
			} catch (ShareNotFound $e) {
				$deleted++;
			}
		}

		if ($deleted > 0) {
			$output->info('Removed ' . $deleted . ' invalid shares');
		}
	}

	public function run(IOutput $output) {
		$this->checkShares($output);
	}
}
