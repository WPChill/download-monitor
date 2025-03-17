import { useMutation } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

const dismissNotice = async ( { id, permanent } ) => {
	const response = await apiFetch( {
		path: `/wpchill/v1/notifications/${ id || '' }`,
		method: 'DELETE',
		data: { permanent },
	} );
	return response;
};

export const useNotificationDismiss = () => {
	return useMutation( {
		mutationFn: dismissNotice,
	} );
};
