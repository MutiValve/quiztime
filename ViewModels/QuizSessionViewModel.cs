using System;
using System.Collections.Generic;
using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Linq;
using System.Runtime.CompilerServices;
using System.Threading.Tasks;
using System.Windows.Input;
using System.Windows.Threading;
using PubQuizApp.Models;
using PubQuizApp.Services;

namespace PubQuizApp.ViewModels
{
    public class QuizSessionViewModel : INotifyPropertyChanged
    {
        private readonly QuizApiService _apiService;
        private readonly DispatcherTimer _timer;

        // API Connection
        private string _baseUrl = "http://localhost:8000";
        private string _connectionStatus = "Ready to connect to API...";
        private bool _isLoading = false;
        private QuizSummary? _selectedQuizSummary;
            
        // Quiz State
        private QuizDetail? _currentQuiz;
        private int _currentQuestionIndex = 0;
        private Question? _currentQuestion;

        // Timer State
        private int _timerSeconds = 30;
        private int _maxTimerSeconds = 30;
        private bool _isTimerRunning = false;

        // Reveal States for Audience Screen
        private bool _showOptions = true;
        private bool _revealAnswer = false;
        private bool _showExplanation = false;
        private bool _isQuizEnded = false;

        public QuizSessionViewModel()
        {
            _apiService = new QuizApiService();
            AvailableQuizzes = new ObservableCollection<QuizSummary>();
            Teams = new ObservableCollection<TeamScore>
            {
                new TeamScore { TeamName = "Team Pub Stars", Score = 0 },
                new TeamScore { TeamName = "Team Quizards", Score = 0 }
            };

            // Setup 1-second DispatcherTimer
            _timer = new DispatcherTimer
            {
                Interval = TimeSpan.FromSeconds(1)
            };
            _timer.Tick += OnTimerTick;

            // Initialize Commands
            ConnectApiCommand = new RelayCommand(async () => await FetchQuizzesAsync());
            LoadQuizCommand = new RelayCommand(async () => await LoadSelectedQuizAsync(), () => SelectedQuizSummary != null);
            
            StartTimerCommand = new RelayCommand(StartTimer);
            PauseTimerCommand = new RelayCommand(PauseTimer);
            ResetTimerCommand = new RelayCommand(ResetTimer);
            AddSecondsCommand = new RelayCommand<int>(AddSeconds);
            SetTimerPresetCommand = new RelayCommand<int>(SetTimerPreset);

            NextQuestionCommand = new RelayCommand(NextQuestion, () => CurrentQuiz != null && CurrentQuestionIndex < TotalQuestions - 1);
            PrevQuestionCommand = new RelayCommand(PrevQuestion, () => CurrentQuiz != null && CurrentQuestionIndex > 0);

            ToggleOptionsCommand = new RelayCommand(ToggleOptions);
            RevealAnswerCommand = new RelayCommand(ToggleRevealAnswer);
            ToggleExplanationCommand = new RelayCommand(ToggleExplanation);

            AddTeamCommand = new RelayCommand(AddTeam);
            EndQuizCommand = new RelayCommand(EndQuiz);
            RestartSessionCommand = new RelayCommand(RestartSession);

            // Auto fetch on launch
            _ = FetchQuizzesAsync();
        }

        // Properties
        public ObservableCollection<QuizSummary> AvailableQuizzes { get; }
        public ObservableCollection<TeamScore> Teams { get; }

        public string BaseUrl
        {
            get => _baseUrl;
            set { _baseUrl = value; OnPropertyChanged(); }
        }

        public string ConnectionStatus
        {
            get => _connectionStatus;
            set { _connectionStatus = value; OnPropertyChanged(); }
        }

        public bool IsLoading
        {
            get => _isLoading;
            set { _isLoading = value; OnPropertyChanged(); }
        }

        public QuizSummary? SelectedQuizSummary
        {
            get => _selectedQuizSummary;
            set
            {
                _selectedQuizSummary = value;
                OnPropertyChanged();
                CommandManager.InvalidateRequerySuggested();
            }
        }

        public QuizDetail? CurrentQuiz
        {
            get => _currentQuiz;
            set
            {
                _currentQuiz = value;
                OnPropertyChanged();
                OnPropertyChanged(nameof(TotalQuestions));
                OnPropertyChanged(nameof(HasQuizLoaded));
                CommandManager.InvalidateRequerySuggested();
            }
        }

        public bool HasQuizLoaded => CurrentQuiz != null && CurrentQuiz.Questions.Count > 0;

        public int CurrentQuestionIndex
        {
            get => _currentQuestionIndex;
            set
            {
                _currentQuestionIndex = value;
                OnPropertyChanged();
                OnPropertyChanged(nameof(QuestionDisplayNumber));
                OnPropertyChanged(nameof(ProgressRatio));
                UpdateCurrentQuestion();
                CommandManager.InvalidateRequerySuggested();
            }
        }

        public Question? CurrentQuestion
        {
            get => _currentQuestion;
            private set
            {
                _currentQuestion = value;
                OnPropertyChanged();
                OnPropertyChanged(nameof(CorrectAnswerText));
            }
        }

        public int TotalQuestions => CurrentQuiz?.Questions.Count ?? 0;
        public string QuestionDisplayNumber => TotalQuestions > 0 ? $"{CurrentQuestionIndex + 1} of {TotalQuestions}" : "0 of 0";
        public double ProgressRatio => TotalQuestions > 0 ? (double)(CurrentQuestionIndex + 1) / TotalQuestions : 0;

        public string CorrectAnswerText
        {
            get
            {
                var correct = CurrentQuestion?.Answers.FirstOrDefault(a => a.IsCorrect);
                return correct != null ? correct.AnswerText : "None specified";
            }
        }

        // Timer Properties
        public int TimerSeconds
        {
            get => _timerSeconds;
            set
            {
                _timerSeconds = Math.Max(0, value);
                OnPropertyChanged();
                OnPropertyChanged(nameof(TimerDisplay));
                OnPropertyChanged(nameof(TimerProgress));
                OnPropertyChanged(nameof(IsTimerWarning));
            }
        }

        public int MaxTimerSeconds
        {
            get => _maxTimerSeconds;
            set
            {
                _maxTimerSeconds = Math.Max(1, value);
                OnPropertyChanged();
                OnPropertyChanged(nameof(TimerProgress));
            }
        }

        public bool IsTimerRunning
        {
            get => _isTimerRunning;
            set { _isTimerRunning = value; OnPropertyChanged(); }
        }

        public string TimerDisplay => $"{TimerSeconds:D2}s";
        public double TimerProgress => MaxTimerSeconds > 0 ? (double)TimerSeconds / MaxTimerSeconds : 0;
        public bool IsTimerWarning => TimerSeconds <= 5 && TimerSeconds > 0;

        // Reveal States
        public bool ShowOptions
        {
            get => _showOptions;
            set { _showOptions = value; OnPropertyChanged(); }
        }

        public bool RevealAnswer
        {
            get => _revealAnswer;
            set { _revealAnswer = value; OnPropertyChanged(); }
        }

        public bool ShowExplanation
        {
            get => _showExplanation;
            set { _showExplanation = value; OnPropertyChanged(); }
        }

        public bool IsQuizEnded
        {
            get => _isQuizEnded;
            set { _isQuizEnded = value; OnPropertyChanged(); }
        }

        // Commands
        public ICommand ConnectApiCommand { get; }
        public ICommand LoadQuizCommand { get; }
        public ICommand StartTimerCommand { get; }
        public ICommand PauseTimerCommand { get; }
        public ICommand ResetTimerCommand { get; }
        public ICommand AddSecondsCommand { get; }
        public ICommand SetTimerPresetCommand { get; }
        public ICommand NextQuestionCommand { get; }
        public ICommand PrevQuestionCommand { get; }
        public ICommand ToggleOptionsCommand { get; }
        public ICommand RevealAnswerCommand { get; }
        public ICommand ToggleExplanationCommand { get; }
        public ICommand AddTeamCommand { get; }
        public ICommand EndQuizCommand { get; }
        public ICommand RestartSessionCommand { get; }

        // Methods
        public async Task FetchQuizzesAsync()
        {
            IsLoading = true;
            ConnectionStatus = $"Connecting to {BaseUrl}...";
            try
            {
                var list = await _apiService.GetQuizzesAsync(BaseUrl);
                AvailableQuizzes.Clear();
                foreach (var q in list)
                {
                    AvailableQuizzes.Add(q);
                }

                if (AvailableQuizzes.Count > 0)
                {
                    SelectedQuizSummary = AvailableQuizzes[0];
                    ConnectionStatus = $"Connected to {BaseUrl} ({AvailableQuizzes.Count} Quiz(zes) available)";
                    _ = LoadSelectedQuizAsync();
                }
                else
                {
                    ConnectionStatus = "Connected, but no quizzes found on server.";
                }
            }
            catch (Exception ex)
            {
                ConnectionStatus = $"API Connection Error: {ex.Message}";
            }
            finally
            {
                IsLoading = false;
            }
        }

        public async Task LoadSelectedQuizAsync()
        {
            if (SelectedQuizSummary == null) return;

            IsLoading = true;
            ConnectionStatus = $"Loading quiz #{SelectedQuizSummary.Id}...";
            try
            {
                var detail = await _apiService.GetQuizDetailAsync(BaseUrl, SelectedQuizSummary.Id);
                if (detail != null)
                {
                    CurrentQuiz = detail;
                    CurrentQuestionIndex = 0;
                    MaxTimerSeconds = detail.DefaultTimer;
                    ResetTimer();
                    ResetStageFlags();
                    IsQuizEnded = false;
                    ConnectionStatus = $"Active Quiz: {detail.Title}";
                }
            }
            catch (Exception ex)
            {
                ConnectionStatus = $"Error loading quiz detail: {ex.Message}";
            }
            finally
            {
                IsLoading = false;
            }
        }

        private void UpdateCurrentQuestion()
        {
            if (CurrentQuiz != null && CurrentQuestionIndex >= 0 && CurrentQuestionIndex < CurrentQuiz.Questions.Count)
            {
                CurrentQuestion = CurrentQuiz.Questions[CurrentQuestionIndex];
                ResetStageFlags();
                ResetTimer();
            }
            else
            {
                CurrentQuestion = null;
            }
        }

        private void ResetStageFlags()
        {
            ShowOptions = true;
            RevealAnswer = false;
            ShowExplanation = false;
        }

        private void OnTimerTick(object? sender, EventArgs e)
        {
            if (TimerSeconds > 0)
            {
                TimerSeconds--;
            }
            else
            {
                PauseTimer();
            }
        }

        private void StartTimer()
        {
            if (TimerSeconds <= 0) ResetTimer();
            _timer.Start();
            IsTimerRunning = true;
        }

        private void PauseTimer()
        {
            _timer.Stop();
            IsTimerRunning = false;
        }

        private void ResetTimer()
        {
            PauseTimer();
            TimerSeconds = MaxTimerSeconds;
        }

        private void AddSeconds(int seconds)
        {
            TimerSeconds += seconds;
            if (TimerSeconds > MaxTimerSeconds) MaxTimerSeconds = TimerSeconds;
        }

        private void SetTimerPreset(int seconds)
        {
            MaxTimerSeconds = seconds;
            TimerSeconds = seconds;
        }

        private void NextQuestion()
        {
            if (CurrentQuestionIndex < TotalQuestions - 1)
            {
                CurrentQuestionIndex++;
            }
            else
            {
                EndQuiz();
            }
        }

        private void PrevQuestion()
        {
            if (CurrentQuestionIndex > 0)
            {
                CurrentQuestionIndex--;
            }
        }

        private void ToggleOptions() => ShowOptions = !ShowOptions;
        private void ToggleRevealAnswer() => RevealAnswer = !RevealAnswer;
        private void ToggleExplanation() => ShowExplanation = !ShowExplanation;

        private void AddTeam()
        {
            int teamNum = Teams.Count + 1;
            Teams.Add(new TeamScore { TeamName = $"Team {teamNum}", Score = 0 });
        }

        private void EndQuiz()
        {
            PauseTimer();
            IsQuizEnded = true;
        }

        private void RestartSession()
        {
            CurrentQuestionIndex = 0;
            IsQuizEnded = false;
            ResetStageFlags();
            ResetTimer();
        }

        public event PropertyChangedEventHandler? PropertyChanged;
        protected void OnPropertyChanged([CallerMemberName] string? propName = null)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propName));
        }
    }

    // Helper RelayCommand
    public class RelayCommand : ICommand
    {
        private readonly Action _execute;
        private readonly Func<bool>? _canExecute;

        public RelayCommand(Action execute, Func<bool>? canExecute = null)
        {
            _execute = execute ?? throw new ArgumentNullException(nameof(execute));
            _canExecute = canExecute;
        }

        public bool CanExecute(object? parameter) => _canExecute?.Invoke() ?? true;
        public void Execute(object? parameter) => _execute();
        public event EventHandler? CanExecuteChanged
        {
            add => CommandManager.RequerySuggested += value;
            remove => CommandManager.RequerySuggested -= value;
        }
    }

    public class RelayCommand<T> : ICommand
    {
        private readonly Action<T> _execute;
        private readonly Predicate<T>? _canExecute;

        public RelayCommand(Action<T> execute, Predicate<T>? canExecute = null)
        {
            _execute = execute ?? throw new ArgumentNullException(nameof(execute));
            _canExecute = canExecute;
        }

        public bool CanExecute(object? parameter) => _canExecute?.Invoke((T)parameter!) ?? true;

        public void Execute(object? parameter)
        {
            if (parameter == null)
                throw new ArgumentNullException(nameof(parameter));

            try
            {
                // Try direct cast first
                if (parameter is T typedParameter)
                {
                    _execute(typedParameter);
                }
                else if (typeof(T) == typeof(int) && parameter is string strParam)
                {
                    // Special handling for string-to-int conversion from XAML CommandParameter
                    if (int.TryParse(strParam, out var intValue))
                        _execute((T)(object)intValue);
                    else
                        throw new InvalidOperationException(
                            $"Cannot convert '{strParam}' to {typeof(T).Name}");
                }
                else
                {
                    throw new InvalidOperationException(
                        $"Expected parameter of type {typeof(T).Name}, but got {parameter.GetType().Name}");
                }
            }
            catch (InvalidOperationException) { throw; }
        }

            public event EventHandler? CanExecuteChanged
        {
            add => CommandManager.RequerySuggested += value;
            remove => CommandManager.RequerySuggested -= value;
        }
    }
}
