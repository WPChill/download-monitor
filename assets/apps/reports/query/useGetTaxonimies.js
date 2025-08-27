import { useQuery, useMutation } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

// Categories
export const useCategories = () => {
	return useQuery( {
		queryKey: [ 'categories' ],
		queryFn: () => apiFetch( { path: 'dlm-page-addon/v1/categories' } ),
	} );
};

// Tags
export const useTags = () => {
	return useQuery( {
		queryKey: [ 'tags' ],
		queryFn: () => apiFetch( { path: 'dlm-page-addon/v1/tags' } ),
	} );
};

// Templates
export const useTemplates = () => {
	return useQuery( {
		queryKey: [ 'templates' ],
		queryFn: () => apiFetch( { path: 'dlm-page-addon/v1/templates' } ),
	} );
};

// Options
export const useOptions = () => {
	return useQuery( {
		queryKey: [ 'options' ],
		queryFn: () =>
			apiFetch( {
				path: 'dlm-page-addon/v1/options',
			} ),
	} );
};

// Save Options
export const useSaveOptions = () => {
	return useMutation( {
		mutationFn: ( options ) => {
			return apiFetch( {
				path: 'dlm-page-addon/v1/options',
				method: 'POST',
				data: options,
			} );
		},
	} );
};

