import { FormEvent, useEffect, useMemo, useState } from 'react'
import { Plus, Search, X } from 'lucide-react'
import { supabase } from '../lib/supabase'
import type { Company } from '../types/crm'
import { useAuth } from '../contexts/AuthContext'

const initialForm = { name: '', neighborhood: '', city: 'Salvador', phone_primary: '', primary_specialty: '', priority: 'medium', temperature: 'cold', potential: 'medium', management_system: '' }

export default function Companies() {
  const { user } = useAuth()
  const [companies, setCompanies] = useState<Company[]>([])
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState(initialForm)
  const [error, setError] = useState<string | null>(null)

  async function load() {
    setLoading(true)
    const { data, error } = await supabase.from('companies').select('*').is('deleted_at', null).order('created_at', { ascending: false }).limit(200)
    if (error) setError(error.message)
    setCompanies((data as Company[]) ?? [])
    setLoading(false)
  }

  useEffect(() => { load() }, [])

  const filtered = useMemo(() => {
    const q = query.toLowerCase().trim()
    if (!q) return companies
    return companies.filter(c => [c.name, c.trade_name, c.neighborhood, c.city, c.primary_specialty, c.phone_primary].some(v => v?.toLowerCase().includes(q)))
  }, [companies, query])

  async function createCompany(e: FormEvent) {
    e.preventDefault()
    setError(null)
    if (!user) return
    const { error } = await supabase.from('companies').insert({
      ...form,
      assigned_to: user.id,
      created_by: user.id,
      data_collection_purpose: 'Prospecção comercial B2B',
      collected_at: new Date().toISOString(),
    })
    if (error) return setError(error.message)
    setForm(initialForm)
    setShowForm(false)
    await load()
  }

  return (
    <div>
      <div className="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-6">
        <div><h1 className="text-2xl font-bold">Empresas / Leads</h1><p className="text-slate-500">Cadastro e qualificação das empresas prospectadas.</p></div>
        <button onClick={() => setShowForm(true)} className="inline-flex items-center justify-center gap-2 bg-slate-950 text-white rounded-lg px-4 py-2"><Plus size={18}/> Novo lead</button>
      </div>

      <div className="bg-white border rounded-xl overflow-hidden">
        <div className="p-4 border-b flex items-center gap-2"><Search size={18} className="text-slate-400"/><input value={query} onChange={e=>setQuery(e.target.value)} className="w-full outline-none" placeholder="Buscar empresa, bairro, cidade, especialidade ou telefone..."/></div>
        <div className="overflow-auto">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 text-left"><tr><th className="p-3">Empresa</th><th className="p-3">Bairro</th><th className="p-3">Especialidade</th><th className="p-3">Prioridade</th><th className="p-3">Temperatura</th><th className="p-3">Score</th></tr></thead>
            <tbody>
              {loading ? <tr><td colSpan={6} className="p-8 text-center">Carregando...</td></tr> : filtered.length === 0 ? <tr><td colSpan={6} className="p-8 text-center text-slate-500">Nenhum lead encontrado.</td></tr> : filtered.map(c => (
                <tr key={c.id} className="border-t hover:bg-slate-50"><td className="p-3 font-medium">{c.name}</td><td className="p-3">{c.neighborhood || '—'}</td><td className="p-3">{c.primary_specialty || '—'}</td><td className="p-3">{c.priority}</td><td className="p-3">{c.temperature}</td><td className="p-3 font-semibold">{c.lead_score}</td></tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {error && <div className="mt-4 text-sm text-red-600">{error}</div>}

      {showForm && <div className="fixed inset-0 bg-black/40 z-50 grid place-items-center p-4"><form onSubmit={createCompany} className="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-auto p-6 space-y-4">
        <div className="flex justify-between items-center"><h2 className="text-xl font-bold">Novo lead</h2><button type="button" onClick={()=>setShowForm(false)}><X/></button></div>
        <div className="grid md:grid-cols-2 gap-4">
          <label className="text-sm">Empresa<input className="mt-1 w-full border rounded-lg px-3 py-2" value={form.name} onChange={e=>setForm({...form,name:e.target.value})} required/></label>
          <label className="text-sm">Especialidade<input className="mt-1 w-full border rounded-lg px-3 py-2" value={form.primary_specialty} onChange={e=>setForm({...form,primary_specialty:e.target.value})}/></label>
          <label className="text-sm">Bairro<input className="mt-1 w-full border rounded-lg px-3 py-2" value={form.neighborhood} onChange={e=>setForm({...form,neighborhood:e.target.value})}/></label>
          <label className="text-sm">Cidade<input className="mt-1 w-full border rounded-lg px-3 py-2" value={form.city} onChange={e=>setForm({...form,city:e.target.value})}/></label>
          <label className="text-sm">Telefone<input className="mt-1 w-full border rounded-lg px-3 py-2" value={form.phone_primary} onChange={e=>setForm({...form,phone_primary:e.target.value})}/></label>
          <label className="text-sm">Sistema atual<input className="mt-1 w-full border rounded-lg px-3 py-2" value={form.management_system} onChange={e=>setForm({...form,management_system:e.target.value})}/></label>
          <label className="text-sm">Prioridade<select className="mt-1 w-full border rounded-lg px-3 py-2" value={form.priority} onChange={e=>setForm({...form,priority:e.target.value})}><option value="low">Baixa</option><option value="medium">Média</option><option value="high">Alta</option></select></label>
          <label className="text-sm">Temperatura<select className="mt-1 w-full border rounded-lg px-3 py-2" value={form.temperature} onChange={e=>setForm({...form,temperature:e.target.value})}><option value="cold">Frio</option><option value="warm">Morno</option><option value="hot">Quente</option></select></label>
        </div>
        <div className="flex justify-end gap-2"><button type="button" onClick={()=>setShowForm(false)} className="border rounded-lg px-4 py-2">Cancelar</button><button className="bg-slate-950 text-white rounded-lg px-4 py-2">Salvar lead</button></div>
      </form></div>}
    </div>
  )
}
