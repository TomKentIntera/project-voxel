interface HeaderProps {
  title: string
  description?: string
}

export default function Header({ title, description }: HeaderProps) {
  return (
    <header className="border-b border-slate-200 bg-white px-8 py-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">{title}</h1>
          {description && (
            <p className="mt-1 text-sm text-slate-500">{description}</p>
          )}
        </div>
        <div className="flex items-center gap-4">
          {/* Notification bell */}
          <button
            type="button"
            className="relative rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
          >
            <svg className="size-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-red-500" />
          </button>
          {/* Avatar */}
          <div className="flex size-8 items-center justify-center rounded-full bg-slate-200 text-xs font-medium text-slate-600">
            A
          </div>
        </div>
      </div>
    </header>
  )
}

