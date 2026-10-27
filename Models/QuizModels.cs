using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Runtime.CompilerServices;
using System.Text.Json.Serialization;

namespace PubQuizApp.Models
{
    public class ApiResponse<T>
    {
        [JsonPropertyName("status")]
        public string Status { get; set; } = string.Empty;

        [JsonPropertyName("count")]
        public int Count { get; set; }

        [JsonPropertyName("data")]
        public T? Data { get; set; }

        [JsonPropertyName("message")]
        public string? Message { get; set; }
    }

    public class QuizSummary
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("title")]
        public string Title { get; set; } = string.Empty;

        [JsonPropertyName("category")]
        public string Category { get; set; } = "General Knowledge";

        [JsonPropertyName("description")]
        public string Description { get; set; } = string.Empty;

        [JsonPropertyName("default_timer")]
        public int DefaultTimer { get; set; } = 30;

        [JsonPropertyName("question_count")]
        public int QuestionCount { get; set; }

        [JsonPropertyName("created_at")]
        public string CreatedAt { get; set; } = string.Empty;

        public string DisplayText => $"{Title} ({QuestionCount} Qs - {Category})";
    }

    public class QuizDetail
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("title")]
        public string Title { get; set; } = string.Empty;

        [JsonPropertyName("category")]
        public string Category { get; set; } = "General Knowledge";

        [JsonPropertyName("description")]
        public string Description { get; set; } = string.Empty;

        [JsonPropertyName("default_timer")]
        public int DefaultTimer { get; set; } = 30;

        [JsonPropertyName("questions")]
        public List<Question> Questions { get; set; } = new();
    }

    public class Question
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("question_text")]
        public string QuestionText { get; set; } = string.Empty;

        [JsonPropertyName("image_url")]
        public string? ImageUrl { get; set; }

        [JsonPropertyName("points")]
        public int Points { get; set; } = 10;

        [JsonPropertyName("order_num")]
        public int OrderNum { get; set; } = 1;

        [JsonPropertyName("explanation")]
        public string? Explanation { get; set; }

        [JsonPropertyName("answers")]
        public List<AnswerChoice> Answers { get; set; } = new();

        public bool HasImage => !string.IsNullOrWhiteSpace(ImageUrl);
        public bool HasExplanation => !string.IsNullOrWhiteSpace(Explanation);
    }

    public class AnswerChoice
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("answer_text")]
        public string AnswerText { get; set; } = string.Empty;

        [JsonPropertyName("is_correct")]
        public bool IsCorrect { get; set; }
    }

    public class TeamScore : INotifyPropertyChanged
    {
        private string _teamName = "Team";
        private int _score = 0;

        public string TeamName
        {
            get => _teamName;
            set { _teamName = value; OnPropertyChanged(); }
        }

        public int Score
        {
            get => _score;
            set { _score = value; OnPropertyChanged(); }
        }

        public event PropertyChangedEventHandler? PropertyChanged;
        protected void OnPropertyChanged([CallerMemberName] string? propName = null)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propName));
        }
    }
}
