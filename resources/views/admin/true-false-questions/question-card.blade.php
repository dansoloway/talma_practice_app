<div class="question-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span class="badge {{ $question->is_true ? 'badge-success' : 'badge-danger' }}" style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                    {{ $question->is_true ? 'TRUE' : 'FALSE' }}
                </span>
                @if(!$question->is_approved)
                    <span class="badge badge-warning" style="background: #ffc107; color: #000; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                        Pending
                    </span>
                @endif
                @if($question->audio_path)
                    <span class="badge badge-info" style="background: #d1ecf1; color: #0c5460; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                        <i class="fas fa-volume-up"></i> Audio
                    </span>
                @else
                    <span class="badge badge-warning" style="background: #fff3cd; color: #856404; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                        <i class="fas fa-volume-mute"></i> No audio
                    </span>
                @endif
            </div>
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: var(--color-text);">{{ $question->statement }}</h3>
            @if($question->explanation)
                <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.875rem;">
                    <strong>Explanation:</strong> {{ $question->explanation }}
                </p>
            @endif
        </div>
        <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
            <a href="{{ route('admin.lessons.true-false-games.questions.edit', [$lesson, $trueFalseGame, $question]) }}" class="btn btn-sm">Edit</a>
            @if(!$question->is_approved)
                <form action="{{ route('admin.lessons.true-false-games.questions.approve', [$lesson, $trueFalseGame, $question]) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                </form>
            @endif
            <form action="{{ route('admin.lessons.true-false-games.questions.destroy', [$lesson, $trueFalseGame, $question]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this question?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
        </div>
    </div>
    
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
        @if($question->vocabulary->isNotEmpty())
            <div style="font-size: 0.875rem; color: #666;">
                <strong>Vocabulary:</strong>
            </div>
            @foreach($question->vocabulary as $vocab)
                <span class="badge badge-info" style="background: #d1ecf1; color: #0c5460; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                    {{ $vocab->english_word }}
                </span>
            @endforeach
        @endif
        @if($question->category)
            <span class="badge badge-secondary" style="background: #e2e3e5; color: #383d41; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                {{ $question->category }}
            </span>
        @endif
    </div>
</div>
