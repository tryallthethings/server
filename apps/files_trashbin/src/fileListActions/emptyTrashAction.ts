/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import PQueue from 'p-queue'
import { FileListAction } from '@nextcloud/files'
import { DialogSeverity, getDialogBuilder, showSuccess } from '@nextcloud/dialogs'

import TrashCanSvg from '@mdi/svg/svg/trash-can.svg?raw'

import { deleteNode } from '../../../files/src/actions/deleteUtils.ts'
import { logger } from '../logger.ts'

const queue = new PQueue({ concurrency: 5 })

export const emptyTrashAction = new FileListAction({
	id: 'empty-trash',

	displayName: () => t('files_trashbin', 'Empty deleted files'),
	iconSvgInline: () => TrashCanSvg,
	order: 0,

	enabled: (view, nodes, { folder }) => {
		if (view.id !== 'trashbin') {
			return false
		}

		return nodes.length > 0 && folder.path === '/'
	},

	exec: async (view, nodes) => {
		const dialog = getDialogBuilder(t('files_trashbin', 'Confirm permanent deletion'))
			.setSeverity(DialogSeverity.Warning)
			// TODO Add note for groupfolders
			.setText(t('files_trashbin', "Are you sure you want to permanently delete the items in deleted files? You can't undo this action."))
			.setButtons([
				{
					label: t('files_trashbin', 'Cancel'),
					type: 'secondary',
					callback: () => {},
				},
				{
					label: t('files_trashbin', 'Empty deleted files'),
					type: 'error',
					callback: async () => {
						for (const node of nodes) {
							queue.add(async () => {
								try {
									await deleteNode(node)
								} catch (error) {
									logger.error('Failed to delete node', { error, node })
								}
							})
						}
						queue.once('empty', () => {
							showSuccess(t('files_trashbin', 'Permanently deleted items in deleted files'))
						})
					},
				},
			])
			.build()

		try {
			await dialog.show()
		} catch (error) {
			// Allow throw on dialog close
		}
	},
})
