import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { destroy, store, update } from '@/actions/App/Http/Controllers/Admin/UserController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Auth } from '@/types';

type ManagedUser = {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  created_at: string;
};

interface Props {
  users: ManagedUser[];
}

type FormState = {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  is_admin: boolean;
};

const emptyForm = (): FormState => ({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  is_admin: false,
});

export default function UsersIndex({ users }: Props) {
  const { auth } = usePage<{ auth: Auth }>().props;
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<ManagedUser | null>(null);
  const [deleting, setDeleting] = useState<ManagedUser | null>(null);
  const [deletingProcessing, setDeletingProcessing] = useState(false);

  const form = useForm<FormState>(emptyForm());

  function openCreate() {
    setEditing(null);
    form.setData(emptyForm());
    form.clearErrors();
    setOpen(true);
  }

  function openEdit(user: ManagedUser) {
    setEditing(user);
    form.setData({
      name: user.name,
      email: user.email,
      password: '',
      password_confirmation: '',
      is_admin: user.is_admin,
    });
    form.clearErrors();
    setOpen(true);
  }

  function submit(e: React.FormEvent) {
    e.preventDefault();

    const options = {
      onSuccess: () => {
        toast.success(editing ? 'User updated.' : 'User added.');
        setOpen(false);
      },
    };

    if (editing) {
      form.transform((data) => ({
        name: data.name,
        email: data.email,
        is_admin: data.is_admin,
      }));
      form.put(update.url(editing.id), options);
    } else {
      form.transform((data) => data);
      form.post(store.url(), options);
    }
  }

  function requestDestroy(user: ManagedUser) {
    if (user.id === auth.user?.id) {
      toast.error('You cannot delete your own account.');

      return;
    }

    setDeleting(user);
  }

  function confirmDestroy() {
    if (!deleting) {
      return;
    }

    setDeletingProcessing(true);

    router.delete(destroy.url(deleting.id), {
      onSuccess: () => {
        toast.success('User removed.');
        setDeleting(null);
      },
      onError: () => toast.error('Unable to delete this user.'),
      onFinish: () => setDeletingProcessing(false),
    });
  }

  return (
    <>
      <Head title="Manage Users" />
      <div className="p-4">
        <div className="mb-4 flex items-center justify-between">
          <h1 className="text-xl font-semibold">Users</h1>
          <Button onClick={openCreate}>
            <Plus className="mr-2 size-4" /> Add User
          </Button>
        </div>
        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <table className="w-full text-sm">
            <thead className="bg-muted/50 text-left">
              <tr>
                <th className="px-4 py-3">Name</th>
                <th className="px-4 py-3">Email</th>
                <th className="px-4 py-3">Role</th>
                <th className="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                    No users yet.
                  </td>
                </tr>
              )}
              {users.map((user) => (
                <tr key={user.id} className="border-t border-sidebar-border/70 dark:border-sidebar-border">
                  <td className="px-4 py-3 font-medium">{user.name}</td>
                  <td className="px-4 py-3 text-muted-foreground">{user.email}</td>
                  <td className="px-4 py-3">
                    <span className={user.is_admin ? 'font-medium text-foreground' : 'text-muted-foreground'}>
                      {user.is_admin ? 'Admin' : 'User'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button variant="ghost" size="icon" onClick={() => openEdit(user)}>
                      <Pencil className="size-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      disabled={user.id === auth.user?.id}
                      onClick={() => requestDestroy(user)}
                    >
                      <Trash2 className="size-4 text-destructive" />
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <ConfirmDeleteDialog
        open={deleting !== null}
        onOpenChange={(nextOpen) => {
          if (!nextOpen) {
            setDeleting(null);
          }
        }}
        title={`Delete ${deleting?.name ?? 'user'}?`}
        description="This permanently removes the user account. This action cannot be undone."
        confirmLabel="Delete user"
        processing={deletingProcessing}
        onConfirm={confirmDestroy}
      />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>{editing ? 'Edit User' : 'Add User'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1">
              <Label htmlFor="user-name">Name</Label>
              <Input
                id="user-name"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
              />
              {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}
            </div>
            <div className="space-y-1">
              <Label htmlFor="user-email">Email</Label>
              <Input
                id="user-email"
                type="email"
                value={form.data.email}
                onChange={(e) => form.setData('email', e.target.value)}
              />
              {form.errors.email && <p className="text-sm text-destructive">{form.errors.email}</p>}
            </div>
            {!editing && (
              <>
                <div className="space-y-1">
                  <Label htmlFor="user-password">Password</Label>
                  <Input
                    id="user-password"
                    type="password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                  />
                  {form.errors.password && <p className="text-sm text-destructive">{form.errors.password}</p>}
                </div>
                <div className="space-y-1">
                  <Label htmlFor="user-password-confirmation">Confirm password</Label>
                  <Input
                    id="user-password-confirmation"
                    type="password"
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                  />
                  {form.errors.password_confirmation && (
                    <p className="text-sm text-destructive">{form.errors.password_confirmation}</p>
                  )}
                </div>
              </>
            )}
            <label className="flex items-center gap-2">
              <Checkbox
                checked={form.data.is_admin}
                disabled={editing?.id === auth.user?.id}
                onCheckedChange={(checked) => form.setData('is_admin', Boolean(checked))}
              />
              <span className="text-sm">Admin</span>
            </label>
            <div className="flex justify-end gap-2 pt-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={form.processing}>
                {editing ? 'Save changes' : 'Add user'}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}

UsersIndex.layout = {
  breadcrumbs: [{ title: 'Users', href: '/admin/users' }],
};
