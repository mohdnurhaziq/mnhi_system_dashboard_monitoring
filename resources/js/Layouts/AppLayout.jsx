import { Link, usePage } from '@inertiajs/react';

const NAV = [
    { name: 'Dashboard', route: 'dashboard', href: '/' },
    { name: 'Projects', route: 'projects.index', href: '/projects' },
    { name: 'Prompts', route: 'prompts.index', href: '/prompts' },
    { name: 'Settings', route: 'settings.edit', href: '/settings' },
];

export default function AppLayout({ title, actions = null, children }) {
    const { url, props } = usePage();
    const flash = props.flash ?? {};

    const isActive = (href) =>
        href === '/' ? url === '/' : url.startsWith(href);

    return (
        <div className="min-h-screen bg-gray-50 text-gray-900">
            <div className="flex">
                {/* Sidebar */}
                <aside className="fixed inset-y-0 left-0 w-56 border-r border-gray-200 bg-white">
                    <div className="flex h-16 items-center px-6">
                        <span className="text-lg font-semibold tracking-tight">
                            System Dashboard
                        </span>
                    </div>
                    <nav className="mt-2 space-y-1 px-3">
                        {NAV.map((item) => (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`block rounded-md px-3 py-2 text-sm font-medium ${
                                    isActive(item.href)
                                        ? 'bg-gray-900 text-white'
                                        : 'text-gray-700 hover:bg-gray-100'
                                }`}
                            >
                                {item.name}
                            </Link>
                        ))}
                    </nav>
                </aside>

                {/* Main */}
                <div className="ml-56 flex-1">
                    <header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-8">
                        <h1 className="text-xl font-semibold">{title}</h1>
                        <div>{actions}</div>
                    </header>

                    {flash.success && (
                        <div className="mx-8 mt-4 rounded-md bg-emerald-50 px-4 py-2 text-sm text-emerald-800 ring-1 ring-emerald-600/20">
                            {flash.success}
                        </div>
                    )}

                    <main className="p-8">{children}</main>
                </div>
            </div>
        </div>
    );
}
