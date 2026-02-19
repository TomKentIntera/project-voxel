import Header from '../components/Header'

const mockJobs = [
  { id: 'job-3891', name: 'ProvisionServerJob', queue: 'default', status: 'completed', duration: '4.2s', completedAt: '2 min ago' },
  { id: 'job-3890', name: 'ProcessPaymentJob', queue: 'billing', status: 'completed', duration: '1.8s', completedAt: '5 min ago' },
  { id: 'job-3889', name: 'BackupServerJob', queue: 'backups', status: 'failed', duration: '300s', completedAt: '25 min ago' },
  { id: 'job-3888', name: 'SendWelcomeEmailJob', queue: 'mail', status: 'completed', duration: '0.3s', completedAt: '31 min ago' },
  { id: 'job-3887', name: 'SyncBillingJob', queue: 'billing', status: 'completed', duration: '2.1s', completedAt: '45 min ago' },
  { id: 'job-3886', name: 'CleanupExpiredTokensJob', queue: 'default', status: 'processing', duration: '—', completedAt: '—' },
  { id: 'job-3885', name: 'GenerateInvoiceJob', queue: 'billing', status: 'pending', duration: '—', completedAt: '—' },
]

const statusColor: Record<string, string> = {
  completed: 'bg-emerald-50 text-emerald-700',
  failed: 'bg-red-50 text-red-700',
  processing: 'bg-blue-50 text-blue-700',
  pending: 'bg-amber-50 text-amber-700',
}

export default function JobsPage() {
  return (
    <>
      <Header title="Jobs" description="Background job queue and processing history" />

      <div className="p-8">
        {/* Queue stats */}
        <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
          <QueueStat label="Pending" value="2" color="text-amber-600" />
          <QueueStat label="Processing" value="1" color="text-blue-600" />
          <QueueStat label="Completed (24h)" value="342" color="text-emerald-600" />
          <QueueStat label="Failed (24h)" value="3" color="text-red-600" />
        </div>

        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Job ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Queue</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Duration</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Completed</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {mockJobs.map((job) => (
                <tr key={job.id} className="hover:bg-slate-50">
                  <td className="whitespace-nowrap px-6 py-4 text-sm font-mono text-slate-900">{job.id}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{job.name}</td>
                  <td className="whitespace-nowrap px-6 py-4">
                    <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                      {job.queue}
                    </span>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4">
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColor[job.status]}`}>
                      {job.status}
                    </span>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{job.duration}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{job.completedAt}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  )
}

function QueueStat({ label, value, color }: { label: string; value: string; color: string }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white px-6 py-4">
      <p className="text-sm font-medium text-slate-500">{label}</p>
      <p className={`mt-1 text-2xl font-semibold ${color}`}>{value}</p>
    </div>
  )
}

