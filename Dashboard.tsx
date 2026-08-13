import { useEffect, useState } from 'react'
import { supabase } from '../lib/supabase'

export default function Dashboard() {
  const [metrics, setMetrics] = useState({ leads: 0, hot: 0, opportunities: 0, tasks: 0 })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    Promise.all([
      supabase.from('companies').select('*', { count: 'exact', head: true }).eq('status', 'lead').is('deleted_at', null),
      supabase.from('companies').select('*', { count: 'exact', head: true }).eq('temperature', 'hot').is('deleted_at', null),
      supabase.from('opportunities').select('*', { count: 'exact', head: true }).eq('status', 'open').is('deleted_at', null),
      supabase.from('tasks').select('*', { count: 'exact', head: true }).in('status', ['pending', 'in_progress', 'overdue']).is('deleted_at', null),
    ]).then(([leads, hot, opportunities, tasks]) => {
      setMetrics({ leads: leads.count ?? 0, hot: hot.count ?? 0, opportunities: opportunities.count ?? 0, tasks: tasks.count ?? 0 })
      setLoading(false)
    })
  }, [])

  const cards = [
    ['Leads ativos', metrics.leads],
    ['Leads quentes', metrics.hot],
    ['Oportunidades abertas', metrics.opportunities],
    ['Tarefas pendentes', metrics.tasks],
  ]

  return (
    <div>
      <div className="mb-6"><h1 className="text-2xl font-bold">Dashboard</h1><p className="text-slate-500">Visão geral da operação comercial.</p></div>
      <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {cards.map(([label, value]) => <div key={String(label)} className="bg-white border rounded-xl p-5"><div className="text-sm text-slate-500">{label}</div><div className="text-3xl font-bold mt-2">{loading ? '—' : value}</div></div>)}
      </div>
    </div>
  )
}
