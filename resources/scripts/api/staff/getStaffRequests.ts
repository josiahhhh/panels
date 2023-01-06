import http from '@/api/http';
import { StaffRequestResponse } from '@/components/dashboard/staff/StaffRequetsContainer';

export default async (): Promise<StaffRequestResponse> => {
    const { data } = await http.get('/api/client/account/staff');
    return data.data || [];
};
