import { useMutation } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

const dismissNotices = () => {
	const response = apiFetch( {
		path: '/wpchill/v1/notifications',
		method: 'DELETE',
	} );
	return response;
};

export const useNotificationsDismiss = () => {
	return useMutation( {
		mutationFn: dismissNotices,
	} );
};
