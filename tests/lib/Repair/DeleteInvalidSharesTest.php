<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Repair;

use OC\Repair\DeleteInvalidShares;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use OCP\Share\IShare;
use Test\TestCase;
use Test\Traits\UserTrait;

/**
 * Tests for repairing invalid shares
 *
 * @group DB
 *
 * @see DeleteInvalidShares
 */
class DeleteInvalidSharesTest extends TestCase {
	use UserTrait;

	private IDBConnection $connection;
	private DeleteInvalidShares $repair;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->deleteAllShares();

		$this->repair = new DeleteInvalidShares();
	}

	protected function tearDown(): void {
		$this->deleteAllShares();

		parent::tearDown();
	}

	protected function deleteAllShares(): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->executeStatement();
	}

	public function testSharesInaccessibleNode(): void {
		// The dummy user will not have the node as we never even create it for the test
		$this->createUser('user1', 'user1');

		$qb = $this->connection->getQueryBuilder();
		$shareValues = [
			'share_type' => $qb->expr()->literal(IShare::TYPE_USER),
			'share_with' => $qb->expr()->literal('user2'),
			'uid_owner' => $qb->expr()->literal('user1'),
			'uid_initiator' => $qb->expr()->literal('user1'),
			'item_type' => $qb->expr()->literal('file'),
			'item_source' => $qb->expr()->literal(123),
			'item_target' => $qb->expr()->literal('/123'),
			'file_source' => $qb->expr()->literal(123),
			'file_target' => $qb->expr()->literal('/123'),
		];

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values($shareValues)
			->executeStatement();
		$id = $qb->getLastInsertId();

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->executeQuery();
		$rows = $result->fetchAll();
		$this->assertEquals([['id' => $id]], $rows);
		$result->closeCursor();

		$outputMock = $this->getMockBuilder(IOutput::class)
			->disableOriginalConstructor()
			->getMock();
		$outputMock
			->expects($this->once())
			->method('info')
			->with('Removed 1 invalid shares');

		$this->repair->run($outputMock);

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->executeQuery();
		$rows = $result->fetchAll();
		$this->assertEquals([], $rows);
		$result->closeCursor();
	}
}
