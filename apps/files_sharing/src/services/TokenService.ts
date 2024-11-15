/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import logger from './logger.ts'

interface TokenData {
	token: string,
}

export const generateToken = async (): Promise<null | string> => {
	try {
		const { data } = await axios.get<TokenData>(generateUrl('/api/v1/token'))
		return data.token
	} catch (error) {
		logger.error('Failed to get token from server, falling back to client-side generation', { error })

		const chars = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789'
		const array = new Uint8Array(10)
		const ratio = chars.length / 255
		window.crypto.getRandomValues(array)
		let token = ''
		for (let i = 0; i < array.length; i++) {
			token += chars.charAt(array[i] * ratio)
		}
		return token
	}
}
