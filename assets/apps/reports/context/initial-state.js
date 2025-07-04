import dayjs from 'dayjs';

export const initialState = () => ( {
	periods: {
		start: dayjs().subtract( 7, 'day' ).format( 'YYYY-MM-DD' ),
		end: dayjs().format( 'YYYY-MM-DD' ),
	},
} );
