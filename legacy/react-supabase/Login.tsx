import { FormEvent, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { supabase } from '../lib/supabase'

export default function Login() {
  const { session, signIn } = useAuth()
  const [mode, setMode] = useState<'login' | 'signup'>('login')
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  if (session) return <Navigate to="/" replace />

  async function submit(e: FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError(null)
    setMessage(null)

    if (mode === 'login') {
      setError(await signIn(email, password))
    } else {
      const { error } = await supabase.auth.signUp({
        email,
        password,
        options: { data: { full_name: name } },
      })
      if (error) setError(error.message)
      else setMessage('Conta criada. Se a confirmação de email estiver habilitada no Supabase, confirme o email antes de entrar.')
    }
    setLoading(false)
  }

  return (
    <div className="min-h-screen grid place-items-center p-4 bg-slate-100">
      <form onSubmit={submit} className="w-full max-w-md bg-white rounded-2xl shadow-sm border p-8 space-y-5">
        <div>
          <h1 className="text-2xl font-bold">CRM X</h1>
          <p className="text-slate-500 mt-1">{mode === 'login' ? 'Entre para gerenciar sua prospecção comercial em saúde.' : 'Crie a primeira conta do CRM.'}</p>
        </div>
        {mode === 'signup' && <div>
          <label className="text-sm font-medium">Nome</label>
          <input className="mt-1 w-full border rounded-lg px-3 py-2" value={name} onChange={e => setName(e.target.value)} required />
        </div>}
        <div>
          <label className="text-sm font-medium">Email</label>
          <input className="mt-1 w-full border rounded-lg px-3 py-2" type="email" value={email} onChange={e => setEmail(e.target.value)} required />
        </div>
        <div>
          <label className="text-sm font-medium">Senha</label>
          <input className="mt-1 w-full border rounded-lg px-3 py-2" type="password" minLength={6} value={password} onChange={e => setPassword(e.target.value)} required />
        </div>
        {error && <div className="text-sm text-red-600">{error}</div>}
        {message && <div className="text-sm text-emerald-700">{message}</div>}
        <button disabled={loading} className="w-full bg-slate-950 text-white rounded-lg py-2.5 font-medium disabled:opacity-60">
          {loading ? 'Processando...' : mode === 'login' ? 'Entrar' : 'Criar conta'}
        </button>
        <button type="button" onClick={() => { setMode(mode === 'login' ? 'signup' : 'login'); setError(null); setMessage(null) }} className="w-full text-sm text-slate-600 hover:text-slate-950">
          {mode === 'login' ? 'Ainda não tenho conta' : 'Já tenho uma conta'}
        </button>
      </form>
    </div>
  )
}
