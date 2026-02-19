interface StatCardProps {
  label: string
  value: string
  icon: React.ReactNode
  isLoading?: boolean
}

export default function StatCard({
  label,
  value,
  icon,
  isLoading = false,
}: StatCardProps) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6">
      <div className="flex items-center justify-between">
        <div className="rounded-lg bg-slate-100 p-2 text-slate-600">
          {icon}
        </div>
      </div>
      <div className="mt-4">
        <p className="text-sm font-medium text-slate-500">{label}</p>
        {isLoading ? (
          <div className="mt-1 h-9 w-24 animate-pulse rounded-md bg-slate-200" />
        ) : (
          <p className="mt-1 text-3xl font-semibold text-slate-900">{value}</p>
        )}
      </div>
    </div>
  )
}
