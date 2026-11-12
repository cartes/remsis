<?php

declare(strict_types=1);

namespace Modules\Companies\Policies;

use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

class CompanyPolicy
{
    /**
     * Perform pre-authorization checks.
     * Super-admins bypass all policy checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        return $user->getAllCompanies()->isNotEmpty();
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company);
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'company.manage');
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        // Solo super-admin (vía before) puede eliminar una empresa / tenant
        return false;
    }

    /**
     * Determine whether the user can manage company users / memberships.
     */
    public function manageUsers(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'users.manage');
    }

    /**
     * Determine whether the user can manage employees within the company.
     */
    public function manageEmployees(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'employees.manage');
    }

    /**
     * Determine whether the user can view payroll for the company.
     */
    public function viewPayroll(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'payroll.view');
    }

    /**
     * Determine whether the user can calculate payroll for the company.
     */
    public function calculatePayroll(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'payroll.calculate');
    }

    /**
     * Determine whether the user can approve payroll for the company.
     */
    public function approvePayroll(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'payroll.approve');
    }

    /**
     * Determine whether the user can manage administrative requests (e.g. permisos, días administrativos).
     */
    public function manageRequests(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'requests.manage');
    }

    /**
     * Determine whether the user can manage employee documents within the company.
     */
    public function manageDocuments(User $user, Company $company): bool
    {
        return $user->canAccessCompany($company)
            && $user->hasCompanyPermission($company, 'documents.manage');
    }
}

