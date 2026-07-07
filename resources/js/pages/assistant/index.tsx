import { AlertCircle, SendHorizonal, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface ChatMessage {
  id: number;
  role: 'user' | 'assistant';
  content: string;
}

function xsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

  return match ? decodeURIComponent(match[1]) : '';
}

async function parseErrorMessage(response: Response): Promise<string> {
  try {
    const data = (await response.json()) as { message?: string; errors?: Record<string, string[]> };

    if (data.errors) {
      const firstError = Object.values(data.errors).flat()[0];

      if (firstError) {
        return firstError;
      }
    }

    if (data.message) {
      return data.message;
    }
  } catch {
    // Fall through to the default message below.
  }

  if (response.status === 422) {
    return 'Please check your message and try again.';
  }

  if (response.status === 429) {
    return 'Too many requests. Please wait a moment and try again.';
  }

  return 'The assistant could not respond right now. Please try again.';
}

export default function Assistant() {
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [conversationId, setConversationId] = useState<string | null>(null);

  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, loading]);

  async function send(text: string) {
    const content = text.trim();

    if (!content || loading) {
      return;
    }

    setMessages((prev) => [...prev, { id: Date.now(), role: 'user', content }]);
    setInput('');
    setError(null);
    setLoading(true);

    try {
      const res = await fetch('/assistant/message', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-XSRF-TOKEN': xsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ message: content, conversation_id: conversationId }),
      });

      if (!res.ok) {
        setError(await parseErrorMessage(res));

        return;
      }

      const data: { reply: string; conversation_id: string | null } = await res.json();
      setConversationId(data.conversation_id);
      setMessages((prev) => [...prev, { id: Date.now() + 1, role: 'assistant', content: data.reply }]);
    } catch {
      setError('Unable to reach the assistant. Check your connection and try again.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto flex h-full w-full max-w-2xl flex-1 flex-col gap-4 p-4">
      {error && (
        <Alert variant="destructive">
          <AlertCircle />
          <AlertTitle>Something went wrong</AlertTitle>
          <AlertDescription className="flex items-start justify-between gap-3">
            <span>{error}</span>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="size-6 shrink-0 text-destructive-foreground hover:bg-destructive/20 hover:text-destructive-foreground"
              onClick={() => setError(null)}
              aria-label="Dismiss error"
            >
              <X className="size-4" />
            </Button>
          </AlertDescription>
        </Alert>
      )}

      <div className="flex-1 space-y-3 overflow-y-auto">
        {messages.map((message) => (
          <div key={message.id} className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[80%] whitespace-pre-wrap rounded-2xl px-4 py-2 text-sm ${message.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'
              }`}>
              {message.content}
            </div>
          </div>
        ))}

        {loading && (
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <span className="animate-pulse">Thinking…</span>
          </div>
        )}
        <div ref={bottomRef} />
      </div>

      <form onSubmit={(e) => {
        e.preventDefault();
        send(input);
      }} className="flex gap-2">
        <Input value={input} onChange={(e) => setInput(e.target.value)} placeholder="Type your message…" disabled={loading} autoFocus />
        <Button type="submit" size="icon" disabled={loading || !input.trim()}>
          <SendHorizonal className="size-4" />
        </Button>
      </form>
    </div>
  );
}
