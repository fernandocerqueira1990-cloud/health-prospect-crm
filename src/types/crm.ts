export type AppRole = 'admin' | 'manager' | 'sales'
export type LeadPriority = 'low' | 'medium' | 'high'
export type LeadTemperature = 'cold' | 'warm' | 'hot'
export type LeadPotential = 'low' | 'medium' | 'high' | 'strategic'

export interface Company {
  id: string
  name: string
  trade_name: string | null
  cnpj: string | null
  city: string | null
  neighborhood: string | null
  phone_primary: string | null
  email: string | null
  website: string | null
  primary_specialty: string | null
  priority: LeadPriority
  temperature: LeadTemperature
  potential: LeadPotential
  lead_score: number
  management_system: string | null
  erp: string | null
  his: string | null
  pacs: string | null
  ris: string | null
  lis: string | null
  status: 'lead' | 'customer' | 'inactive'
  created_at: string
}

export interface Profile {
  id: string
  full_name: string | null
  role: AppRole
  active: boolean
}
