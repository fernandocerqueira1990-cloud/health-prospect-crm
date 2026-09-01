import { Building2, LayoutDashboard, LogOut, Target, Users, CheckSquare, Menu } from 'lucide-react'
import { NavLink, Outlet } from 'react-router-dom'
import { useState } from 'react'
import { useAuth } from '../contexts/AuthContext'

const nav = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/empresas', label: 'Empresas', icon: Building2 },
  { to: '/contatos', label: 'Contatos', icon: Users },
  { to: '/oportunidades', label: 'Oportunidades', icon: Target },
  { to: '/tarefas', label: 'Tarefas', icon: CheckSquare },
]

export function Layout() {
  const { profile, signOut } = useAuth()
  const [open, setOpen] = useState(false)

  return (
    <div className="min-h-screen flex">
      <aside className={`${open ? 'block' : 'hidden'} md:block w-64 bg-slate-950 text-white p-4 fixed md:static inset-y-0 z-30`}>
        <div className="mb-8">
          <div className="text-lg font-bold">CRM X</div>
          <div className="text-xs text-slate-400">Prospecção B2B em Saúde</div>
        </div>
        <nav className="space-y-1">
          {nav.map(({ to, label, icon: Icon }) => (
            <NavLink key={to} to={to} onClick={() => setOpen(false)} className={({ isActive }) => `flex items-center gap-3 px-3 py-2 rounded-lg ${isActive ? 'bg-slate-800' : 'hover:bg-slate-900'}`}>
              <Icon size={18} /> {label}
            </NavLink>
          ))}
        </nav>
        <button onClick={signOut} className="mt-8 w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-900 text-left">
          <LogOut size={18} /> Sair
        </button>
      </aside>

      <main className="flex-1 min-w-0">
        <header className="h-16 bg-white border-b flex items-center justify-between px-4 md:px-6 sticky top-0 z-20">
          <button className="md:hidden" onClick={() => setOpen(!open)}><Menu /></button>
          <div className="ml-auto text-sm text-slate-600">
            {profile?.full_name || 'Usuário'} · {profile?.role || 'perfil'}
          </div>
        </header>
        <div className="p-4 md:p-6"><Outlet /></div>
      </main>
    </div>
  )
}
