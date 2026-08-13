import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import type { Session, User } from '@supabase/supabase-js'
import { supabase } from '../lib/supabase'
import type { Profile } from '../types/crm'

type AuthContextValue = {
  session: Session | null
  user: User | null
  profile: Profile | null
  loading: boolean
  signIn: (email: string, password: string) => Promise<string | null>
  signOut: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSession] = useState<Session | null>(null)
  const [profile, setProfile] = useState<Profile | null>(null)
  const [loading, setLoading] = useState(true)

  async function loadProfile(userId: string) {
    const { data } = await supabase
      .from('profiles')
      .select('id, full_name, role, active')
      .eq('id', userId)
      .maybeSingle()
    setProfile((data as Profile | null) ?? null)
  }

  useEffect(() => {
    supabase.auth.getSession().then(async ({ data }) => {
      setSession(data.session)
      if (data.session?.user.id) await loadProfile(data.session.user.id)
      setLoading(false)
    })

    const { data: listener } = supabase.auth.onAuthStateChange(async (_event, nextSession) => {
      setSession(nextSession)
      if (nextSession?.user.id) await loadProfile(nextSession.user.id)
      else setProfile(null)
      setLoading(false)
    })

    return () => listener.subscription.unsubscribe()
  }, [])

  const value = useMemo<AuthContextValue>(() => ({
    session,
    user: session?.user ?? null,
    profile,
    loading,
    signIn: async (email, password) => {
      const { error } = await supabase.auth.signInWithPassword({ email, password })
      return error?.message ?? null
    },
    signOut: async () => { await supabase.auth.signOut() },
  }), [session, profile, loading])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const value = useContext(AuthContext)
  if (!value) throw new Error('useAuth deve ser usado dentro de AuthProvider')
  return value
}
