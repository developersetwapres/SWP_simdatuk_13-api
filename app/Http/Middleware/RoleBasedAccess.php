<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccess
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role_id) {
            return response()->json([
                'code' => 403,
                'message' => 'Access denied. Authentication required.',
                'data' => null,
            ], 403);
        }

        $permissionName = $permission ?? $this->getPermissionFromRoute($request);
        $requiredAction = $this->getRequiredAction($request);

        if (! $permissionName) {
            return $next($request);
        }

        if (! $this->checkUserPermission($user->role_id, $permissionName, $requiredAction)) {
            return response()->json([
                'code' => 403,
                'message' => "Access denied. You don't have permission to {$requiredAction} {$permissionName}.",
                'data' => null,
            ], 403);
        }

        return $next($request);
    }

    private function getPermissionFromRoute(Request $request): ?string
    {
        $path = parse_url($request->getRequestUri(), PHP_URL_PATH);

        if ($path === '/api/employees' || str_starts_with($path, '/api/employees/')) {
            return $this->getEmployeePermissionByType($request);
        }

        if ($path === '/api/import-employees') {
            return $this->getEmployeePermissionByType($request);
        }

        if ($path === '/api/training-histories' || str_starts_with($path, '/api/training-histories/')) {
            return $this->getTrainingPermissionByType($request);
        }

        $routePermissionMap = [
            '/api/recapitulations' => 'Rekapitulasi - Komposisi Pegawai',
            '/api/recapitulations-asn' => 'Rekapitulasi - Pegawai ASN',
            '/api/recapitulations-nonasn' => 'Rekapitulasi - Pegawai Non ASN',
            '/api/recapitulations-outsource' => 'Rekapitulasi - Pegawai Outsourcing',
            '/api/recapitulations-employee' => 'Rekapitulasi - Komposisi Pegawai',
            '/api/diagrams' => 'Rekapitulasi - Peta Jabatan',
            '/api/comparisons' => 'Rekapitulasi - Bandingkan Pegawai',
            '/api/promotions' => 'Rekapitulasi - Promosi Pegawai',
            '/api/position-histories' => 'Data Riwayat - Jabatan',
            '/api/grade-histories' => 'Data Riwayat - Golongan',
            '/api/recognition-histories' => 'Data Riwayat - Penghargaan',
            '/api/target-histories' => 'Data Riwayat - SKP',
            '/api/performance-histories' => 'Data Riwayat - Penilaian Prestasi Kerja',
            '/api/disciplinary-histories' => 'Data Riwayat - Hukuman Disiplin',
            '/api/users' => 'Master Data - Data Pengguna',
            '/api/roles' => 'Master Data - Data Role Pengguna',
            '/api/permissions' => 'Master Data - Data Role Pengguna',
            '/api/positions' => 'Master Data - Data Jabatan',
            '/api/institutions' => 'Master Data - Data Instansi',
            '/api/grades' => 'Master Data - Data Golongan',
            '/api/employment-types' => 'Master Data - Jenis Pegawai',
            '/api/exports' => 'Export',
            '/api/export-comparisons' => 'Export',
            '/api/export-recapitulations' => 'Export',
            '/api/notes' => 'Catatan',
            '/api/talents' => 'Hasil Talent Pool',
        ];

        $cleanPath = rtrim($path, '/');

        if (isset($routePermissionMap[$cleanPath])) {
            return $routePermissionMap[$cleanPath];
        }

        foreach ($routePermissionMap as $pattern => $mappedPermission) {
            if (str_starts_with($cleanPath, $pattern)) {
                return $mappedPermission;
            }
        }

        return null;
    }

    private function getRequiredAction(Request $request): string
    {
        return match ($request->getMethod()) {
            'GET' => 'read',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'read',
        };
    }

    private function checkUserPermission(int $roleId, string $permissionName, string $action): bool
    {
        return DB::table('permissions as p')
            ->join('role_permissions as rp', 'p.id', '=', 'rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->where('p.name', $permissionName)
            ->where("rp.{$action}", true)
            ->exists();
    }

    private function getEmployeePermissionByType(Request $request): string
    {
        $type = $request->query('type') ?? $request->input('type') ?? '1';

        return match ($type) {
            '1' => 'Data Pegawai - ASN',
            '2' => 'Data Pegawai - Non ASN',
            '3' => 'Data Pegawai - Outsourcing',
            default => 'Data Pegawai - ASN',
        };
    }

    private function getTrainingPermissionByType(Request $request): string
    {
        $type = $request->query('type') ?? $request->input('type') ?? '1';

        return match ($type) {
            '1' => 'Data Riwayat - Pelatihan Struktural',
            '2' => 'Data Riwayat - Pelatihan Fungsional',
            '3' => 'Data Riwayat - Pelatihan Teknis',
            default => 'Data Riwayat - Pelatihan Struktural',
        };
    }
}
